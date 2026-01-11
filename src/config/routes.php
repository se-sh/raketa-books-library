<?php

return [
    // Registration for new users
    ['POST', '/register', 'UserController@register'],

    // Login + get auth token
    ['POST', '/login', 'AuthController@login'],

    // Show all users
    ['GET',  '/users', 'UserController@index'],

    // Allow access to library for another user by user ID
    ['POST', '/users/{id}/access', 'UserController@grant'],

    // Show all user book
    ['GET', '/books', 'BookController@index'],

    // Create book
    ['POST', '/books', 'BookController@store'],

    // Open book by ID
    ['GET', '/books/{id}', 'BookController@show'],

    // Change book by ID
    ['PUT', '/books/{id}', 'BookController@update'],

    // Delete book by ID (soft)
    ['DELETE', '/books/{id}', 'BookController@destroy'],

    // Restore deleted book by ID
    ['POST', '/books/{id}/restore', 'BookController@restore'],

    // Show books by another user by ID
    ['GET', '/users/{id}/books', 'BookController@userBooks'],

    // Find external book (Google books or MIF)
    ['GET', '/search', 'BookController@searchExternalBooks'],
];
