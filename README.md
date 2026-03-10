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
