#!/bin/bash
set -eo pipefail
# Note: on n'utilise pas -u car certaines variables peuvent être vides de manière légitime

# Script d'analyse SOLID avec Ollama
# Usage: ./check-solid.sh [MODEL_NAME] [BASE_REF] [HEAD_REF]
# Exemple: ./check-solid.sh llama3.2 HEAD^ HEAD

MODEL_NAME="${1:-llama3.2}"
BASE_REF="${2:-HEAD^}"
HEAD_REF="${3:-HEAD}"

echo "🔍 Analyse SOLID avec Ollama (modèle: $MODEL_NAME)"
echo "📊 Comparaison: $BASE_REF..$HEAD_REF"
echo ""

echo "Recherche des fichiers PHP modifiés..."
CHANGED_FILES=$(git diff --name-only "$BASE_REF" "$HEAD_REF" | grep '\.php$' || true)

if [ -z "$CHANGED_FILES" ]; then
  echo "✅ Aucun fichier PHP modifié, analyse SOLID ignorée."
  exit 0
fi

echo "📝 Fichiers PHP modifiés détectés:"
echo "$CHANGED_FILES" | sed 's/^/  - /'
echo ""

FAILED=0

WORKSPACE="${GITHUB_WORKSPACE:-$(pwd)}"
REPORT_DIR="$WORKSPACE/.github/solid-reports"
mkdir -p "$REPORT_DIR"
REPORT_FILE="$REPORT_DIR/solid-report.md"

# En-tête du rapport
cat > "$REPORT_FILE" <<EOF
# 🔍 Rapport d'analyse SOLID

Analyse effectuée avec le modèle **$MODEL_NAME** sur les fichiers PHP modifiés.

EOF

for FILE in $CHANGED_FILES; do
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "📄 Analyse de: $FILE"
  echo ""

  if [ ! -f "$FILE" ]; then
    echo "⚠️  Fichier supprimé, ignoré."
    echo ""
    continue
  fi

  # Construire le prompt pour Ollama
  PROMPT=$(cat <<'PROMPT_EOF'
Tu es un expert PHP/Symfony et des principes SOLID.

Analyse le fichier suivant et détermine s'il respecte les principes SOLID, en particulier :
- SRP (Single Responsibility Principle) : une classe doit avoir une seule raison de changer
- OCP (Open/Closed Principle) : ouvert à l'extension, fermé à la modification
- LSP (Liskov Substitution Principle) : les objets dérivés doivent être substituables à leurs classes de base
- ISP (Interface Segregation Principle) : préférer plusieurs interfaces spécifiques à une interface générale
- DIP (Dependency Inversion Principle) : dépendre d'abstractions, pas de concrétions

IMPORTANT: Réponds UNIQUEMENT avec du JSON valide, sans texte avant ou après. Commence directement par { et termine par }.

Format JSON requis :

{
  "file": "chemin/du/fichier.php",
  "solid_ok": true,
  "problems": [],
  "score": 85
}

ou si problèmes détectés :

{
  "file": "chemin/du/fichier.php",
  "solid_ok": false,
  "problems": [
    {
      "principle": "SRP",
      "severity": "major",
      "summary": "La classe a plusieurs responsabilités",
      "suggestion": "Séparer en plusieurs classes",
      "line": 42
    }
  ],
  "score": 60
}

FICHIER:
PROMPT_EOF
)

  FULL_PROMPT="$PROMPT $FILE

CODE:
$(cat "$FILE")"

  echo "🤖 Interrogation de l'IA..."
  RAW_RESPONSE=$(printf "%s\n" "$FULL_PROMPT" | ollama run "$MODEL_NAME" 2>&1 || echo '{"error": "Erreur lors de l appel à Ollama"}')

  # 1) Nettoyer les séquences ANSI (spinner, couleurs, etc.)
  CLEAN_RESPONSE=$(printf "%s\n" "$RAW_RESPONSE" | sed -r 's/\x1B\[[0-9;?]*[ -/]*[@-~]//g')

  # 2) Essayer d'extraire un bloc JSON à partir de la première ligne contenant un guillemet et un ":"
  # (typiquement la ligne "file": "...", etc.)
  JSON_RESPONSE=$(printf "%s\n" "$CLEAN_RESPONSE" | awk 'found{print} /"[a-zA-Z0-9_]+":/{if(!found){found=1; print}}')

  # Si on n'a rien, tenter à partir de la première accolade
  if [ -z "$JSON_RESPONSE" ]; then
    JSON_RESPONSE=$(printf "%s\n" "$CLEAN_RESPONSE" | awk 'found{print} /{/{if(!found){found=1; print}}')
  fi

  if [ -z "$JSON_RESPONSE" ]; then
    echo "⚠️  Réponse sans bloc JSON pour $FILE"
    echo "Réponse brute (extrait) :"
    echo "$CLEAN_RESPONSE" | head -40
    echo ""
    continue
  fi

  # 3) Si ça ne commence pas par une accolade, on entoure avec { ... }
  if ! echo "$JSON_RESPONSE" | grep -q '^{'; then
    JSON_RESPONSE="{\n$JSON_RESPONSE\n}"
  fi

  # 4) Si ça ne finit pas par une accolade, on ajoute "}"
  if ! echo "$JSON_RESPONSE" | grep -q '}$'; then
    JSON_RESPONSE="$JSON_RESPONSE\n}"
  fi

  # 5) Vérifier que c'est bien du JSON
  if ! echo "$JSON_RESPONSE" | jq . >/dev/null 2>&1; then
    echo "⚠️  JSON invalide pour $FILE, ignoré."
    echo "JSON candidat (extrait) :"
    echo "$JSON_RESPONSE" | head -40
    echo ""
    continue
  fi

  echo "📊 Résultat de l'analyse:"
  echo "$JSON_RESPONSE" | jq .



  SOLID_OK=$(echo "$JSON_RESPONSE" | jq -r '.solid_ok // false')
  SCORE=$(echo "$JSON_RESPONSE" | jq -r '.score // 0')
  PROBLEMS_COUNT=$(echo "$JSON_RESPONSE" | jq '.problems // [] | length')
  MAJOR_PROBLEMS=$(echo "$JSON_RESPONSE" | jq '[.problems // [] | .[] | select(.severity == "major")] | length')

  # --- écriture dans le rapport ---
  echo "" >> "$REPORT_FILE"
  echo "## 📄 $FILE" >> "$REPORT_FILE"
  echo "" >> "$REPORT_FILE"

  if [ "$SOLID_OK" = "true" ]; then
    echo "✅ **Statut**: Conforme aux principes SOLID" >> "$REPORT_FILE"
  else
    echo "❌ **Statut**: Violations SOLID détectées" >> "$REPORT_FILE"
  fi

  echo "" >> "$REPORT_FILE"
  echo "**Score**: $SCORE/100" >> "$REPORT_FILE"
  echo "**Problèmes détectés**: $PROBLEMS_COUNT ($MAJOR_PROBLEMS majeurs)" >> "$REPORT_FILE"
  echo "" >> "$REPORT_FILE"

  if [ "$PROBLEMS_COUNT" -gt 0 ]; then
    echo "$JSON_RESPONSE" | jq -r '
      .problems[]? |
      "### " + (.principle // "") + " - " + (.severity // "") + "\n\n" +
      "**Problème**: " + (.summary // "") + "\n\n" +
      "**Suggestion**: " + (.suggestion // "") + "\n"
    ' >> "$REPORT_FILE"
  fi

  # --- statut CI ---
  if [ "$SOLID_OK" = "false" ] && [ "$MAJOR_PROBLEMS" -gt 0 ]; then
    echo "❌ Violations SOLID majeures détectées dans $FILE"
    FAILED=1
  elif [ "$SOLID_OK" = "true" ]; then
    echo "✅ Fichier conforme aux principes SOLID"
  else
    echo "⚠️  Violations mineures détectées (ne bloque pas la CI)"
  fi

  echo ""
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 Résumé de l'analyse:"
cat "$REPORT_FILE"
echo ""

echo "$REPORT_FILE" > "$REPORT_DIR/report-path.txt"

if [ "$FAILED" -ne 0 ]; then
  echo "❌ Au moins un fichier contient des violations SOLID majeures."
  echo "📄 Rapport complet disponible dans: $REPORT_FILE"
  exit 1
fi

echo "✅ Analyse SOLID terminée : aucun problème majeur détecté."
exit 0
