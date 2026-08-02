<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Las propiedades públicas quedan disponibles como variables en la vista;
     * los atributos sueltos de un componente de clase no lo estarían.
     */
    public function __construct(
        public string $title = 'Panel',
        public string $eyebrow = 'Panel',
        public ?string $heading = null,
    ) {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
