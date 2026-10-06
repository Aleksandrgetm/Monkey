<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PDO;

class DocumentStorage
{
    public function store(Document $document, UploadedFile $file): void
    {
        $contents = fopen($file->getRealPath(), 'rb');
        try {
            $statement = DB::connection()->getPdo()->prepare('UPDATE documents SET file_content = ? WHERE id = ?');
            $statement->bindParam(1, $contents, PDO::PARAM_LOB);
            $statement->bindValue(2, $document->id, PDO::PARAM_INT);
            $statement->execute();
        } finally {
            if (is_resource($contents)) {
                fclose($contents);
            }
        }
    }

    public function contents(Document $document): string
    {
        $contents = DB::table('documents')->where('id', $document->id)->value('file_content');

        return is_resource($contents) ? stream_get_contents($contents) : (string) $contents;
    }
}
