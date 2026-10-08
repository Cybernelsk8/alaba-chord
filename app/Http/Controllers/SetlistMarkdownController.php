<?php

namespace App\Http\Controllers;

use App\Models\Setlist;
use App\Services\SetlistMarkdownExporter;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SetlistMarkdownController extends Controller
{
    public function __invoke(Setlist $setlist, SetlistMarkdownExporter $exporter): Response
    {
        $filename = Str::slug($setlist->title).'-cancionero.md';

        return response($exporter->export($setlist))
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
