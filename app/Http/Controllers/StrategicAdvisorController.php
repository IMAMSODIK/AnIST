<?php

namespace App\Http\Controllers;

use App\Http\Requests\AskAdvisorRequest;
use App\Http\Requests\StoreAdvisorDocumentRequest;
use App\Models\AdvisorDocument;
use App\Models\AdvisorMessage;
use App\Services\AdvisorChatService;
use Illuminate\Http\Request;

/**
 * Strategic Advisor — alur knowledge base + Q&A:
 *
 *   1. User mengunggah beberapa dokumen (tiap file maks 50MB) → sistem
 *      mengekstrak teks PER HALAMAN dan menyimpannya (ingest, tanpa AI).
 *   2. User mengetik pertanyaan / permintaan saran → sistem melakukan
 *      retrieval halaman relevan lintas dokumen, lalu Gemini menjawab
 *      DENGAN sitasi "dokumen X halaman Y" + tren internet terkini
 *      (Google Search grounding).
 */
class StrategicAdvisorController extends Controller
{
    public function __construct(
        protected AdvisorChatService $service,
    ) {}

    /**
     * Halaman utama: knowledge base dokumen + panel chat Q&A
     * (pesan terakhir dimuat sebagai state awal Alpine).
     */
    public function index()
    {
        // PENTING: select kolom ringan SAJA (tanpa pages_json yang bisa
        // berukuran MB per baris). SELECT * + ORDER BY pada kolom JSON besar
        // memicu "Out of sort memory" di MySQL karena filesort memuat seluruh
        // baris ke sort buffer.
        $documents = AdvisorDocument::with('user')
            ->latest()
            ->paginate(20, [
                'id', 'user_id', 'name', 'document_type', 'company', 'period',
                'total_pages', 'char_count', 'status', 'error_message',
                'processing_time', 'created_at', 'updated_at',
            ], 'doc_page')
            ->withQueryString();

        // Idem: tanpa raw_response_json (bisa besar) saat ORDER BY + LIMIT.
        $messages = AdvisorMessage::with('user')
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(15)
            ->get([
                'id', 'user_id', 'question', 'answer', 'citations_json',
                'trends_json', 'recommendations_json', 'grounded', 'status',
                'error_message', 'processing_time', 'created_at',
            ])
            ->reverse()
            ->values();

        // Serialisasi JSON di controller — Blade @json() tidak mendukung
        // ekspresi multi-baris.
        $documentsJson = $documents->getCollection()->map(fn (AdvisorDocument $d) => [
            'id'            => $d->id,
            'name'          => $d->name,
            'document_type' => $d->document_type,
            'company'       => $d->company,
            'period'        => $d->period,
            'total_pages'   => $d->total_pages,
            'char_count'    => $d->char_count,
            'status'        => $d->status,
            'error_message' => $d->status === 'completed' && $d->error_message ? $d->error_message : null,
            'created_at'    => $d->created_at?->diffForHumans(),
            'delete_url'    => route('strategic-advisor.documents.destroy', $d),
        ])->values()->all();

        $messagesJson = $messages->map(fn (AdvisorMessage $m) => [
            'id'              => $m->id,
            'question'        => $m->question,
            'answer'          => $m->answer,
            'citations'       => $m->citations_array,
            'trends'          => $m->trends_array,
            'recommendations' => $m->recommendations_array,
            'grounded'        => (bool) $m->grounded,
            'status'          => $m->status,
            'error_message'   => $m->error_message,
            'processing_time' => $m->processing_time !== null ? (float) $m->processing_time : null,
            'created_at'      => $m->created_at?->diffForHumans(),
            'pending'         => false,
        ])->values()->all();

        return view('strategic-advisor.index', compact('documents', 'messages', 'documentsJson', 'messagesJson'));
    }

    /**
     * JSON endpoint untuk upload dokumen via AJAX (satu file per-request,
     * frontend meng-upload berurutan). Ekstraksi per halaman ~5-30 detik
     * untuk dokumen besar; TIDAK memanggil Gemini sehingga jauh lebih
     * cepat daripada alur lama.
     */
    public function storeDocument(StoreAdvisorDocumentRequest $request): \Illuminate\Http\JsonResponse
    {
        set_time_limit(300);

        $document = $this->service->ingestDocument(
            $request->file('file'),
            $request->user()->id,
        );

        return response()->json([
            'success'    => $document->status === 'completed',
            'status'     => $document->status,
            'document'   => [
                'id'            => $document->id,
                'name'          => $document->name,
                'document_type' => $document->document_type,
                'company'       => $document->company,
                'period'        => $document->period,
                'total_pages'   => $document->total_pages,
                'char_count'    => $document->char_count,
                'status'        => $document->status,
                'error_message' => $document->error_message,
                'created_at'    => $document->created_at?->diffForHumans(),
                'delete_url'    => route('strategic-advisor.documents.destroy', $document),
            ],
            'error_message' => $document->error_message,
        ], 200);
    }

    /** Hapus dokumen dari knowledge base (beserta file fisiknya). */
    public function destroyDocument(Request $request, AdvisorDocument $document)
    {
        $this->service->deleteDocument($document);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('strategic-advisor.index')
            ->with('success', 'Dokumen "' . $document->name . '" dihapus dari knowledge base.');
    }

    /**
     * JSON endpoint untuk bertanya / meminta saran. Retrieval + Gemini
     * grounded call bisa memakan 15-60 detik.
     */
    public function ask(AskAdvisorRequest $request): \Illuminate\Http\JsonResponse
    {
        set_time_limit(300);

        $message = $this->service->ask(
            $request->input('question'),
            $request->user()->id,
        );

        return response()->json([
            'success' => $message->status === 'completed',
            'status'  => $message->status,
            'message' => [
                'id'              => $message->id,
                'question'        => $message->question,
                'answer'          => $message->answer,
                'citations'       => $message->citations_array,
                'trends'          => $message->trends_array,
                'recommendations' => $message->recommendations_array,
                'grounded'        => (bool) $message->grounded,
                'status'          => $message->status,
                'error_message'   => $message->error_message,
                'processing_time' => $message->processing_time !== null
                                        ? (float) $message->processing_time
                                        : null,
                'created_at'      => $message->created_at?->diffForHumans(),
            ],
            'error_message' => $message->error_message,
        ], 200);
    }

    /**
     * Full riwayat tanya-jawab dengan filter status sederhana.
     */
    public function history(Request $request)
    {
        // Tanpa raw_response_json (kolom besar) — hindari "Out of sort memory".
        $query = AdvisorMessage::with('user')->latest()->select([
            'id', 'user_id', 'question', 'answer', 'citations_json',
            'trends_json', 'grounded', 'status', 'error_message',
            'processing_time', 'created_at',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->paginate(15)->withQueryString();

        return view('strategic-advisor.history', compact('records'));
    }

    /** Detail satu pesan (pertanyaan + jawaban lengkap dengan sitasi). */
    public function show(AdvisorMessage $advisorMessage)
    {
        $advisorMessage->load('user');

        return view('strategic-advisor.show', ['message' => $advisorMessage]);
    }
}
