<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentQueryRequest;
use App\Http\Requests\DocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\DocumentQuery;
use App\Services\DocumentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(DocumentQueryRequest $request, DocumentQuery $documents): JsonResponse
    {
        return $this->paginated($documents->build($request->user(), $request->validated(), $request->is('api/admin/*'))->paginate($request->integer('per_page', 20)), DocumentResource::class);
    }

    public function store(DocumentRequest $request, DocumentStorage $storage): JsonResponse
    {
        $document = DB::transaction(function () use ($request, $storage): Document {
            $file = $request->file('file');
            $data = $request->safe()->except('file');
            $document = $request->user()->documents()->create([...$data, 'file_name' => $file->getClientOriginalName(), 'file_type' => $file->getMimeType(), 'file_size' => $file->getSize()]);
            $storage->store($document, $file);
            if ($request->user()->in_app_notifications) {
                $request->user()->alerts()->create([
                    'document_id' => $document->id,
                    'message' => 'Dokuments “'.($document->name ?: $document->file_name).'” ir veiksmīgi pievienots.',
                    'kind' => 'system',
                    'status' => 0,
                    'notification_date' => now(),
                    'deduplication_key' => 'document-uploaded:'.$document->id,
                    'in_app' => true,
                ]);
            }

            return Document::metadata()->findOrFail($document->id)->load(['category', 'product']);
        });

        return response()->json(new DocumentResource($document), 201);
    }

    public function show(Request $request, Document $document): DocumentResource
    {
        $this->authorizeDocument($request, $document, 'view');
        $document->load(['category', 'product']);
        if ($request->is('api/admin/*')) {
            $document->load('owner');
        }

        return new DocumentResource($document);
    }

    public function update(DocumentRequest $request, Document $document, DocumentStorage $storage): DocumentResource
    {
        $this->authorizeDocument($request, $document, 'update');
        DB::transaction(function () use ($request, $document, $storage): void {
            $document->fill($request->safe()->except('file'));
            if ($file = $request->file('file')) {
                $document->fill(['file_name' => $file->getClientOriginalName(), 'file_type' => $file->getMimeType(), 'file_size' => $file->getSize()]);
                $storage->store($document, $file);
            }
            $document->save();
        });

        return $this->show($request, $document);
    }

    public function destroy(Request $request, Document $document): Response
    {
        $this->authorizeDocument($request, $document, 'delete');
        $request->validate(['confirmed' => ['required', 'accepted']]);
        $document->delete();

        return response()->noContent();
    }

    public function file(Request $request, Document $document, DocumentStorage $storage): StreamedResponse
    {
        $this->authorizeDocument($request, $document, 'view');
        $name = str_replace(["\r", "\n", '/', '\\'], '_', $document->file_name);
        $disposition = $request->routeIs('*.download') ? 'attachment' : 'inline';

        return response()->streamDownload(function () use ($storage, $document): void {
            echo $storage->contents($document);
        }, $name, ['Content-Type' => $document->file_type, 'Content-Length' => $document->file_size, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => "sandbox; default-src 'none'"], $disposition);
    }

    private function authorizeDocument(Request $request, Document $document, string $ability): void
    {
        if (! $request->is('api/admin/*')) {
            abort_unless($document->user_id === $request->user()->id, 404);
        }
        Gate::authorize($request->is('api/admin/*') ? 'moderate' : $ability, $document);
    }
}
