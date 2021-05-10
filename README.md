Symfony Demo App with Turbo
===========================

This adds Turbo to the demo for testing purposes with the web debug toolbar.

```
git clone https://github.com/weaverryan/symfony-demo.git sf_demo_turbo
cd sf_demo_turbo
git checkout -b turbo origin/turbo
composer install

# clone Symfony fork and link
git clone https://github.com/weaverryan/symfony.git
cd symfony
git checkout -b sf-debug-turbo-52 origin/sf-debug-turbo-52
cd ..
./symfony/link

# start the app
php bin/console doctrine:fixtures:load
symfony serve
```
