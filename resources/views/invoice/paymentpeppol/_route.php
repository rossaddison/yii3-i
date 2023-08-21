<?php

use App\Invoice\PaymentPeppol\PaymentPeppolController;
Group::create('/invoice')
    ->routes(
    // PaymentPeppol
    Route::get('/paymentpeppol')
                          ->middleware(Authentication::class)
        ->action([PaymentPeppolController::class, 'index'])
        ->name('paymentpeppol/index'),    
    // Add
    Route::methods([Method::GET, Method::POST], '/paymentpeppol/add')
        ->middleware(Authentication::class)
        ->action([PaymentPeppolController::class, 'add'])
        ->name('paymentpeppol/add'),
    // Edit 
    Route::methods([Method::GET, Method::POST], '/paymentpeppol/edit/{id}')
        ->name('paymentpeppol/edit')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
        ->middleware(Authentication::class)
        ->action([PaymentPeppolController::class, 'edit']), 
    Route::methods([Method::GET, Method::POST], '/paymentpeppol/delete/{id}')
        ->name('paymentpeppol/delete')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
        ->middleware(Authentication::class)
        ->action([PaymentPeppolController::class, 'delete']),
    Route::methods([Method::GET, Method::POST], '/paymentpeppol/view/{id}')
        ->name('paymentpeppol/view')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
        ->middleware(Authentication::class)
        ->action([PaymentPeppolController::class, 'view']),
    )
?>        