<?php

use App\Invoice\ProductProperty\ProductPropertyController;
Group::create('/invoice')
    ->routes(
    // ProductProperty
    Route::get('/productproperty')
                          ->middleware(Authentication::class)
        ->action([ProductPropertyController::class, 'index'])
        ->name('productproperty/index'),    
    // Add
    Route::methods([Method::GET, Method::POST], '/productproperty/add')
        ->middleware(Authentication::class)
        ->action([ProductPropertyController::class, 'add'])
        ->name('productproperty/add'),
    // Edit 
    Route::methods([Method::GET, Method::POST], '/productproperty/edit/{id}')
        ->name('productproperty/edit')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
        ->middleware(Authentication::class)
        ->action([ProductPropertyController::class, 'edit']), 
    Route::methods([Method::GET, Method::POST], '/productproperty/delete/{id}')
        ->name('productproperty/delete')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
        ->middleware(Authentication::class)
        ->action([ProductPropertyController::class, 'delete']),
    Route::methods([Method::GET, Method::POST], '/productproperty/view/{id}')
        ->name('productproperty/view')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
        ->middleware(Authentication::class)
        ->action([ProductPropertyController::class, 'view']),
    )
?>        