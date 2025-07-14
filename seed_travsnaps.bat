@echo off
echo Running Travsnap Seeder...
php artisan db:seed --class=TravsnapSeeder
echo Done!
pause
