<?php

use App\Invoice\Client\ClientController;
Group::create('/invoice')
    ->routes(
    // Client
    Route::get('/client')
                          ->middleware(Authentication::class)
        ->action([ClientController::class, 'index'])
        ->name('client/index'),    
    // Add
    Route::methods([Method::GET, Method::POST], '/client/add')
        ->middleware(Authentication::class)
        ->action([ClientController::class, 'add'])
        ->name('client/add'),
    // Edit 
    Route::methods([Method::GET, Method::POST], '/client/edit/{id}')
        ->name('client/edit')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
        ->middleware(Authentication::class)
        ->action([ClientController::class, 'edit']), 
    Route::methods([Method::GET, Method::POST], '/client/delete/{id}')
        ->name('client/delete')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
        ->middleware(Authentication::class)
        ->action([ClientController::class, 'delete']),
    Route::methods([Method::GET, Method::POST], '/client/view/{id}')
        ->name('client/view')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
        ->middleware(Authentication::class)
        ->action([ClientController::class, 'view']),
    )
?>        