<?php

use App\Invoice\Upload\UploadController
    // Upload    Route::get('/upload                           '         )
        ->middleware(Authentication::class)
        ->action([UploadController::class, 'index'])
        ->name('upload/index'),    
    // Add
    Route::methods([Method::GET, Method::POST], '/upload/add')
        ->middleware(Authentication::class)
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editUpload'))
        ->action([UploadController::class, 'add'])
        ->name('upload/add'),
    // Edit 
    Route::methods([Method::GET, Method::POST], '/upload/edit/{id}')
        ->name('upload/edit')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editUpload'))
        ->middleware(Authentication::class)
        ->action([UploadController::class, 'edit']), 
    Route::methods([Method::GET, Method::POST], '/upload/delete/{id}')
        ->name('upload/delete')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editUpload'))
        ->middleware(Authentication::class)
        ->action([UploadController::class, 'delete']),
    Route::methods([Method::GET, Method::POST], '/upload/view/{id}')
        ->name('upload/view')
        ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editUpload'))
        ->middleware(Authentication::class)
        ->action([UploadController::class, 'view']),
?>        