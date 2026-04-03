#!/bin/bash
# Script to create imagePullSecret for private GitHub Container Registry
# Only needed if your repository is PRIVATE

set -e

echo "========================================="
echo "Create imagePullSecret for ghcr.io"
echo "========================================="
echo ""

# Prompt for inputs
read -p "GitHub Username: " GITHUB_USERNAME
read -sp "GitHub Personal Access Token (with read:packages): " GITHUB_TOKEN
echo ""
read -p "GitHub Email: " GITHUB_EMAIL

echo ""
echo "Creating secret in region-eu namespace..."
kubectl create secret docker-registry ghcr-secret \
  --docker-server=ghcr.io \
  --docker-username="$GITHUB_USERNAME" \
  --docker-password="$GITHUB_TOKEN" \
  --docker-email="$GITHUB_EMAIL" \
  -n region-eu \
  --dry-run=client -o yaml | kubectl apply -f -

echo "Creating secret in region-us namespace..."
kubectl create secret docker-registry ghcr-secret \
  --docker-server=ghcr.io \
  --docker-username="$GITHUB_USERNAME" \
  --docker-password="$GITHUB_TOKEN" \
  --docker-email="$GITHUB_EMAIL" \
  -n region-us \
  --dry-run=client -o yaml | kubectl apply -f -

echo ""
echo "✅ Secrets created successfully!"
echo ""
echo "Now uncomment these lines in helm/values.yaml:"
echo "  imagePullSecrets:"
echo "    - name: ghcr-secret"
echo ""
