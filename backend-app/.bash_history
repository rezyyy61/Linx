php artisan optimize:clear
php artisan config:clear
php artisan route:clear
exit
php artisan optimize:clear
exit
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
exit
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
exit
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
docker compose exec -T php php artisan make:migration create_posts_table
php artisan optimize:clear
php artisan migrate
php artisan migrate
php artisan migrate
php artisan migrate
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
exit
php artisan optimize:clear
php artisan optimize:clear
exit
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
exit
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan optimize:clear
php artisan make:migration create_follow_requests_table
php artisan migrate
php artisan make:model Follow/FollowRequest
php artisan make:policy Follow/FollowRequestPolicy --model="App\Models\Follow\FollowRequest"
php artisan cache:clear && php artisan route:clear
php artisan make:controller Api/Follow/FollowRequestController
php artisan optimize:clear
exit
