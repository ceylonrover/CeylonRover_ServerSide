@echo off
echo Running blog seeder to populate test data...
cd /d %~dp0
php artisan db:seed --class=BlogSeeder
echo Done! The database has been populated with test blog data.
pause
