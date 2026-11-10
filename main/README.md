# CheckPoint Laravel application

This directory is the application root. Run Composer, npm, and Artisan commands here. The repository README contains the [Windows setup and walkthrough](../README.md), and [docs/Implementation.md](docs/Implementation.md) tracks development milestones.

```powershell
composer install
npm ci
npm run build
php artisan test --compact
```

Configure the ignored `.env` and `.env.testing` first using the templates and separate MySQL accounts described in the setup guide. Link this directory in Herd with `herd link websys2`; the document root is `public/`.
