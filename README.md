# Scopa 2 Backend

Laravel backend application for Scopa 2 card game, deployed on K3s with multi-region support.

## 🚀 Deployment

This project uses **GitHub Actions** for automated CI/CD deployment to K3s clusters.

### Quick Setup
1. See **[QUICK_START.md](./QUICK_START.md)** for rapid setup checklist
2. See **[DEPLOYMENT.md](./DEPLOYMENT.md)** for detailed configuration guide

### Automated Deployment
- Push to `main` branch triggers automatic deployment
- Builds Docker image and pushes to GitHub Container Registry
- Deploys to all regional namespaces (region-eu, region-us) via Tailscale + SSH
- Rolling updates with automatic health checks

### Manual Deployment
See [DEPLOYMENT.md](./DEPLOYMENT.md#manual-deployment) for manual deployment instructions.

---

## API testing (Postman)

Use these headers to avoid HTML redirects:

- `Content-Type: application/json`
- `Accept: application/json`

Example:

```
POST /api/games/{gameId}/action
Body: { "action": "5Bx(3B+2S)" }
```

## To check
Il simbolo di scopa `#` lo facciamo mandare dal client o lo mettiamo noi?
