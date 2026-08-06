<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PreviewCetakController extends Controller
{
    /**
     * Purely client-side mockup tool — the uploaded design never leaves the
     * browser (no upload endpoint, no storage), so there's nothing for this
     * controller to do beyond rendering the page.
     */
    public function index(): View
    {
        return view('preview-cetak.index');
    }
}
