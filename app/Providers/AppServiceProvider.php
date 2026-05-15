<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\View\Composers\SidebarAllComposer;
use Illuminate\Support\Facades\Session;


class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }


    public function boot(): void
    {
        View::composer('layouts.partials.sidebar-all', SidebarAllComposer::class);

        View::composer('*', function ($view) {
        $cart = Session::get('cart', []);
        $count = 0;

        foreach ($cart as $line) {
            $count += (int)($line['quantity'] ?? 0);
        }

        $view->with('cartItemCount', $count);
    });
    }
}
