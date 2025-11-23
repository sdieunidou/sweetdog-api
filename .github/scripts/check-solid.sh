#!/bin/bash
set -eo pipefail
# Note: on n'utilise pas -u car certaines variables peuvent être vides de manière légitime

# Script d'analyse SOLID avec Ollama
# Usage: ./check-solid.sh [MODEL_NAME] [BASE_REF]
# Exemple: ./check-solid.sh llama3.2 HEAD^ HEAD

MODEL_NAME="${1:-llama3.2}"
BASE_REF="${2:-HEAD^}"
HEAD_REF="${3:-HEAD}"

echo "🔍 Analyse SOLID avec Ollama (modèle: $MODEL_NAME)"
echo "📊 Comparaison: $BASE_REF..$HEAD_REF"
echo ""

# Récupérer les fichiers PHP modifiés
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
# Créer un répertoire pour les rapports dans le workspace
# GITHUB_WORKSPACE est défini dans GitHub Actions, sinon on utilise le répertoire courant
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

  FULL_PROMPT="$PROMPT$FILE

CODE:
$(cat "$FILE")"

  # Appeler Ollama et capturer la réponse
  echo "🤖 Interrogation de l'IA..."
  RESPONSE=$(printf "%s\n" "$FULL_PROMPT" | ollama run "$MODEL_NAME" 2>&1 || echo '{"error": "Erreur lors de l\'appel à Ollama"}')

  # Extraire le JSON de la réponse (parfois Ollama ajoute du texte avant/après)
  # On cherche le premier bloc JSON valide dans la réponse
  # Méthode: Extraire tout entre la première { et la dernière } correspondante
  FIRST_BRACE=$(echo "$RESPONSE" | grep -n '{' | head -1 | cut -d: -f1 || echo "")
  LAST_BRACE=$(echo "$RESPONSE" | grep -n '}' | tail -1 | cut -d: -f1 || echo "")
  
  if [ -n "$FIRST_BRACE" ] && [ -n "$LAST_BRACE" ] && [ "$FIRST_BRACE" -le "$LAST_BRACE" ]; then
    JSON_RESPONSE=$(echo "$RESPONSE" | sed -n "${FIRST_BRACE},${LAST_BRACE}p")
  else
    JSON_RESPONSE=""
  fi
  
  # Si l'extraction échoue, essayer de trouver du JSON valide avec jq
  if [ -z "$JSON_RESPONSE" ] || ! echo "$JSON_RESPONSE" | jq . >/dev/null 2>&1; then
    # Essayer d'extraire le JSON en cherchant toutes les lignes entre { et }
    # et en les assemblant
    JSON_LINES=$(echo "$RESPONSE" | awk '/{/,/}/' || echo "")
    if [ -n "$JSON_LINES" ]; then
      JSON_RESPONSE=$(echo "$JSON_LINES" | jq -s '.' 2>/dev/null | jq '.[0]' 2>/dev/null || echo "$JSON_LINES")
    fi
  fi

  if [ -z "$JSON_RESPONSE" ]; then
    echo "⚠️  Réponse invalide ou non-JSON pour $FILE"
    echo "Réponse brute:"
    echo "$RESPONSE" | head -20
    echo ""
    continue
  fi

  # Valider le JSON
  if ! echo "$JSON_RESPONSE" | jq . >/dev/null 2>&1; then
    echo "⚠️  JSON invalide pour $FILE, ignoré."
    echo "Réponse brute:"
    echo "$RESPONSE" | head -20
    echo ""
    continue
  fi

  # Afficher le résultat formaté
  echo "📊 Résultat de l'analyse:"
  echo "$JSON_RESPONSE" | jq .

  # Extraire les informations
  SOLID_OK=$(echo "$JSON_RESPONSE" | jq -r '.solid_ok // false')
  SCORE=$(echo "$JSON_RESPONSE" | jq -r '.score // 0')
  PROBLEMS_COUNT=$(echo "$JSON_RESPONSE" | jq '.problems // [] | length')
  MAJOR_PROBLEMS=$(echo "$JSON_RESPONSE" | jq '[.problems // [] | .[] | select(.severity == "major")] | length')

  # Ajouter au rapport
  {
    echo ""
    echo "## 📄 $FILE"
    echo ""
    if [ "$SOLID_OK" = "true" ]; then
      echo "✅ **Statut**: Conforme aux principes SOLID"
    else
      echo "❌ **Statut**: Violations SOLID détectées"
    fi
    echo ""
    echo "**Score**: $SCORE/100"
    echo "**Problèmes détectés**: $PROBLEMS_COUNT ($MAJOR_PROBLEMS majeurs)"
    echo ""

    if [ "$PROBLEMS_COUNT" -gt 0 ]; then
      echo "$JSON_RESPONSE" | jq -r '.problems[]? | "### \(.principle) - \(.severity)\n\n**Problème**: \(.summary)\n\n**Suggestion**: \(.suggestion)\n\n"' >> "$REPORT_FILE"
    fi
  } >> "$REPORT_FILE"

  # Vérifier si on doit faire échouer la CI
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

# Afficher le résumé
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 Résumé de l'analyse:"
cat "$REPORT_FILE"
echo ""

# Sauvegarder le chemin du rapport dans un fichier pour la CI
echo "$REPORT_FILE" > "$REPORT_DIR/report-path.txt"

if [ "$FAILED" -ne 0 ]; then
  echo "❌ Au moins un fichier contient des violations SOLID majeures."
  echo "📄 Rapport complet disponible dans: $REPORT_FILE"
  exit 1
fi

echo "✅ Analyse SOLID terminée : aucun problème majeur détecté."
exit 0

