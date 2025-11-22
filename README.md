# Pizza Restro

Simple Pizza Restaurant Management System built with PHP, MySQL and Bootstrap.

Features
- User registration/login with role-based admin access
- Menu management (pizzas, beverages, sides, dips)
- Shopping cart and order placement
- Admin order dashboard and food item CRUD

Run locally (XAMPP)
1. Place project in `htdocs` (already in `c:/xampp/htdocs/pizza_restro`).
2. Create a MySQL database named `pizza_restaurant` and import `pizza_restaurant.sql` or run `init_db.php`.
3. Configure database credentials in `db.php` (do NOT commit real credentials).
4. Start Apache + MySQL in XAMPP and open `http://localhost/pizza_restro`.

GitHub
- Before pushing, review sensitive files (e.g., `db.php`).
- Use `db.example.php` as a template and keep secrets out of the repository.

To create a GitHub repo and push:
1. Create a new repo on GitHub via the website or using GitHub CLI (`gh repo create`).
2. Add remote and push:
```
git remote add origin https://github.com/<your-username>/<repo-name>.git
git push -u origin main
```

Security note
- Replace any real credentials with environment-based config before publishing.
