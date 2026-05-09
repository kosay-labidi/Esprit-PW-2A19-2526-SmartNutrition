## API (backend) — GaiaLumen

Ce dossier contient des endpoints API utilisés par le frontend.

### `anthropic-messages.php`

Proxy serveur IA pour le chat. Il utilise Groq côté serveur (pour ne pas exposer la clé côté navigateur) et renvoie un format compatible avec le frontend existant.

#### Variable d'environnement requise

- `GROQ_API_KEY`
- `GROQ_MODEL` optionnel, par défaut `llama-3.3-70b-versatile`

#### Exemple (Windows / PowerShell)

Dans la session où Apache/PHP tourne (ou via la config Apache) :

```powershell
$env:GROQ_API_KEY="VOTRE_CLE"
```

Puis recharger Apache.

### `me.php`

Retourne l’utilisateur courant depuis la session PHP (`user_id`, `nom`, `pseudo`, `email`) pour afficher le bon nom dans le chat.

### `chat-upload.php`

Upload d’image (multipart `file`) pour le chat. Les fichiers sont stockés sous `api/uploads/chat/YYYY/MM/` et l’URL relative renvoyée au frontend.
