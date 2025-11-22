# Pizza Restro

Pizza Restro is a lightweight Pizza Restaurant Management System built with PHP, MySQL and Bootstrap. It demonstrates a complete small-business web app workflow including a public menu, shopping cart, user authentication, and an admin dashboard for managing menu items and orders.

Key features
- User registration and secure login with role-based admin access
- Dynamic menu management (pizzas, beverages, sides, dips) with CRUD operations
- Shopping cart and order placement with order-tracking in admin panel
- Responsive UI using Bootstrap and server-side input validation

Technology stack
- PHP (plain PHP files)
- MySQL (database: `pizza_restaurant`)
- Bootstrap 5 for layout and styling
- No build step required — designed to run on XAMPP / LAMP

Repository layout (important files)
- `index.php` — Home page
- `menu.php` — Public menu and add-to-cart flows
- `order.php` — Checkout and order placement
- `admin.php`, `admin_food.php` — Admin dashboard and food management
- `db.php` — Database connection (DO NOT commit credentials)
- `db.example.php` — Example DB config (use this to create `db.php` locally)
- `pizza_restaurant.sql` — SQL dump to create schema + sample data

Local setup (Windows + XAMPP)
1. Ensure XAMPP is installed and Apache + MySQL are running.
2. Place the project folder in `C:\xampp\htdocs\` (already at `C:\xampp\htdocs\pizza_restro`).
3. Create the database and tables:

	Option A — Import SQL file using phpMyAdmin:

	- Open `http://localhost/phpmyadmin`
	- Create a database named `pizza_restaurant`
	- Import `pizza_restaurant.sql` from the project root

	Option B — Run the provided initializer (works if `db.php` is configured):

	```powershell
	# from project root
	# create db.php first (copy db.example.php -> db.php and edit credentials)
	php init_db.php
	```

4. Configure database connection:

	- Copy `db.example.php` to `db.php` and update `$user` / `$password` if needed.
	- `db.php` is intentionally excluded from the repo; keep it local and secret.

	Example (Windows PowerShell):

	```powershell
	cd C:\xampp\htdocs\pizza_restro
	copy db.example.php db.php
	# then open db.php in an editor and fill credentials
	```

5. Start the app in a browser:

	- Visit: `http://localhost/pizza_restro`

Development workflow (suggested)
1. Create a feature branch locally: `git checkout -b feature/<name>`
2. Make changes, run locally in XAMPP and test pages (register, menu, admin flows)
3. Commit small, focused changes with clear messages
4. Push branch and create a pull request on GitHub for review

Publishing to GitHub
- Ensure `db.php` is not committed (use `db.example.php`), and `.gitignore` contains local files you don't want pushed.
- Add a remote and push:

```powershell
cd C:\xampp\htdocs\pizza_restro
git remote add origin https://github.com/<your-username>/<repo-name>.git
git push -u origin main
```

Security & cleanup notes
- Do NOT commit real credentials. Use `db.example.php` for repository configuration.
- If you accidentally committed secrets, stop and follow a history-cleaning workflow (ask me if you need help). I can help rewrite history safely using `git filter-repo`.

Troubleshooting
- If you see DB connection errors, re-check `db.php` credentials and ensure the `pizza_restaurant` database exists.
- If images don’t show, confirm the `assets/` path is present and files were not removed by `.gitignore` rules.

License
- Add a `LICENSE` file if you want to publish under an open-source license (e.g., MIT). I can add one on request.

Contact / Next steps
- Tell me if you want a `LICENSE`, GitHub Actions workflow for CI, or a simple `.htaccess` to harden the project.
