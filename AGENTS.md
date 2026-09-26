# Repository Guidelines

## Project Structure

This is a server-rendered PHP 8.0+ and MySQL site. Pages such as `index.php` and `collection.php` live in the root. Shared templates, PDO queries, and authentication helpers are in `includes/`; POST handlers are in `actions/`; configuration is in `config/`; schema is in `database/`; CLI tools are in `scripts/`. Plant seeds and blog content are in `data/`; assets are in `css/`, `js/`, and `images/`; integration tests are in `tests/`.

## Run and Check Locally

- In XAMPP, start Apache and MySQL, then visit `http://localhost/Web503073/`.
- Run `C:\xampp\php\php.exe scripts/setup-database.php` to create tables and seed missing plants without overwriting data.
- Run `C:\xampp\php\php.exe scripts/create-admin.php` to create an administrator interactively.
- To run PHP's built-in server from the project root, use `C:\xampp\php\php.exe -S 127.0.0.1:8090 -t .`, then open `http://127.0.0.1:8090/`.
- Check PHP syntax for every page and handler in PowerShell:

  ```powershell
  Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
  ```

There is no build, package manager, or configured formatter.

## Coding Style

Use four spaces, `declare(strict_types=1);` in PHP entry points, procedural PHP, and vanilla JavaScript. Keep shared markup in `includes/` and request handlers in `actions/`. Use lowercase kebab case filenames, such as `favorite-action.php`. Match surrounding CSS formatting. Escape HTML values with `e()` from `includes/init.php`. For state-changing forms, use POST and the existing CSRF helpers; validate input before processing it.

## Testing

Run syntax checks and `C:\xampp\php\php.exe tests/run.php` after backend changes. The standalone PHP runner creates a disposable database and web server, checks account, catalog, favorite, profile, and admin flows through HTTP, and removes test resources. MySQL must be running; tests need database creation/deletion privileges and PHP curl. No coverage target is configured. Check affected layouts and normal, empty, and invalid states manually in a browser.

## Commits and Pull Requests

Recent commits use short imperative summaries, sometimes with prefixes such as `feat:` and `Refactor:`. Keep commits focused; examples include `Fix collection filter` and `feat: add profile sidebar`. Pull requests should describe the user-visible change, list the pages affected, provide local verification steps, and include screenshots for visual changes. Link a related issue when one exists.

## Security and Configuration

Never commit real credentials; use the ignored `config/database.local.php` or `DB_*` environment variables. Use prepared statements, preserve CSRF checks, and use `safe_return_url()` for redirects. Require login for private pages and admin access for user management. Public registration must never assign administrator privileges; blocking must invalidate existing sessions.
