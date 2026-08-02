<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_raiz_lleva_al_panel(): void
    {
        // El backend no sirve nada público: la parte pública es el frontend Next.js.
        $this->get('/')->assertRedirect('/admin');
    }
}
