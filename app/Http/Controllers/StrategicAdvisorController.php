<?php

namespace App\Http\Controllers;

use App\Http\Requests\AskAdvisorRequest;
use App\Http\Requests\StoreAdvisorDocumentRequest;
use App\Models\AdvisorDocument;
use App\Models\AdvisorMessage;
use App\Models\AdvisorSession;
use App\Services\AdvisorChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Strategic Advisor — alur knowledge base + Q&A:
 *
 *   1. User mengunggah beberapa dokumen (tiap file maks 50MB) → sistem
 *      mengekstrak teks PER HALAMAN dan menyimpannya (ingest, tanpa AI).
 *   2. User mengajukan pertanyaan dalam SESI CHAT (ala ChatGPT) →
 *      sistem melakukan retrieval halaman relevan lintas dokumen, lalu
 *      Gemini menjawab DENGAN sitasi "dokumen X halaman Y" + tren
 *      internet terkini (Google Search grounding).
 *
 * Halaman /strategic-advisor memakai layout chat mandiri (tanpa sidebar
 * aplikasi) dan dibuka di tab baru dari menu sidebar utama.
 */
class StrategicAdvisorController extends Controller
{
    public function __construct(
        protected AdvisorChatService $service,
    ) {}

    /**
     * Halaman chat standalone (ala ChatGPT): sidebar riwayat sesi milik
     * user + area chat. Parameter ?session={id} membuka sesi tertentu.
     */
    public function index(Request $request)
    {
        $sessions = AdvisorSession::where('user_id', $request->user()->id)
            ->orderByDesc('last_activity_at')
            ->limit(50)
            ->get(['id', 'title', 'message_count', 'last_activity_at']);

        // Sesi aktif: dari query string, atau sesi terbaru.
        $activeSession = null;
        $messages = collect();
        $documentsCount = AdvisorDocument::where('status', 'completed')->count();

        if ($request->filled('session')) {
            $activeSession = AdvisorSession::where('user_id', $request->user()->id)
                ->find($request->integer('session'));
        } else {
            $activeSession = AdvisorSession::where('user_id', $request->user()->id)
                ->orderByDesc('last_activity_at')
                ->first();
        }

        if ($activeSession) {
            // Tanpa raw_response_json (kolom besar) — hemat memori.
            $messages = AdvisorMessage::where('advisor_session_id', $activeSession->id)
                ->oldest()
                ->limit(100)
                ->get([
                    'id', 'question', 'answer', 'citations_json', 'trends_json',
                    'recommendations_json', 'grounded', 'status', 'error_message',
                    'processing_time', 'created_at',
                ]);
        }

        $sessionsJson = $sessions->map(fn (AdvisorSession $s) => $this->sessionPayload($s))->values()->all();
        $messagesJson = $messages->map(fn (AdvisorMessage $m) => $this->messagePayload($m, false))->values()->all();

        return view('strategic-advisor.chat', [
            'sessionsJson'    => $sessionsJson,
            'messagesJson'    => $messagesJson,
            'activeSessionId' => $activeSession?->id,
            'documentsCount'  => $documentsCount,
        ]);
    }

    /** JSON: daftar sesi chat milik user terurut aktivitas terbaru. */
    public function sessionsIndex(Request $request): JsonResponse
    {
        $sessions = AdvisorSession::where('user_id', $request->user()->id)
            ->orderByDesc('last_activity_at')
            ->limit(50)
            ->get(['id', 'title', 'message_count', 'last_activity_at']);

        return response()->json([
            'success' => true,
            'sessions' => $sessions->map(fn (AdvisorSession $s) => $this->sessionPayload($s))->values()->all(),
        ]);
    }

    /** JSON: buat sesi chat baru. */
    public function storeSession(Request $request): JsonResponse
    {
        $session = $this->service->createSession($request->user()->id);

        return response()->json([
            'success' => true,
            'session' => $this->sessionPayload($session),
        ], 201);
    }

    /** JSON: isi satu sesi (daftar pesan). */
    public function showSession(Request $request, AdvisorSession $session): JsonResponse
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403, 'Sesi bukan milik Anda.');
        }

        $messages = AdvisorMessage::where('advisor_session_id', $session->id)
            ->oldest()
            ->limit(100)
            ->get([
                'id', 'question', 'answer', 'citations_json', 'trends_json',
                'recommendations_json', 'grounded', 'status', 'error_message',
                'processing_time', 'created_at',
            ]);

        return response()->json([
            'success' => true,
            'session' => $this->sessionPayload($session->fresh()),
            'messages' => $messages->map(fn (AdvisorMessage $m) => $this->messagePayload($m, false))->values()->all(),
        ]);
    }

    /** Hapus sesi chat (beserta pesan-pesannya). */
    public function destroySession(Request $request, AdvisorSession $session): JsonResponse
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403, 'Sesi bukan milik Anda.');
        }

        $session->messages()->delete();
        $session->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Halaman "Dokumen / Knowledge Base" — upload dokumen + daftar dokumen
     * terpaginasi. Terpisah dari halaman chat Q&A.
     */
    public function documents()
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

        $documentsJson = $documents->getCollection()->map(fn (AdvisorDocument $d) => [
            'id' => $d->id,
            'name' => $d->name,
            'document_type' => $d->document_type,
            'company' => $d->company,
            'period' => $d->period,
            'total_pages' => $d->total_pages,
            'char_count' => $d->char_count,
            'status' => $d->status,
            'error_message' => $d->status === 'completed' && $d->error_message ? $d->error_message : null,
            'created_at' => $d->created_at?->diffForHumans(),
            'delete_url' => route('strategic-advisor.documents.destroy', $d),
        ])->values()->all();

        return view('strategic-advisor.documents', compact('documents', 'documentsJson'));
    }

    /**
     * JSON endpoint untuk upload dokumen via AJAX (satu file per-request,
     * frontend meng-upload berurutan). Endpoint ini hanya menyimpan file dan
     * membuat record berstatus 'processing'. Ekstraksi dilakukan bertahap
     * melalui processDocument() oleh browser.
     */
    public function storeDocument(StoreAdvisorDocumentRequest $request): JsonResponse
    {
        $document = $this->service->storeDocument(
            $request->file('file'),
            $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'status' => $document->status,
            'document' => $this->documentPayload($document),
            'process_url' => route('strategic-advisor.documents.process', $document),
            'error_message' => null,
        ], 200);
    }

    /**
     * JSON endpoint untuk memproses SATU CHUNK ekstraksi halaman. Frontend
     * memanggil ini berulang sampai status 'completed'.
     */
    public function processDocument(Request $request, AdvisorDocument $document): JsonResponse
    {
        if ($document->user_id !== $request->user()->id) {
            abort(403, 'Dokumen bukan milik Anda.');
        }

        $document = $this->service->processDocumentChunk($document);

        return response()->json([
            'success' => $document->status === 'completed',
            'status' => $document->status,
            'pages_done' => count($document->pages_json ?? []),
            'total_pages' => $document->total_pages,
            'document' => $this->documentPayload($document),
            'error_message' => $document->status === 'failed' ? $document->error_message : null,
        ], 200);
    }

    /** Bentuk payload dokumen untuk frontend. */
    private function documentPayload(AdvisorDocument $document): array
    {
        return [
            'id' => $document->id,
            'name' => $document->name,
            'document_type' => $document->document_type,
            'company' => $document->company,
            'period' => $document->period,
            'total_pages' => $document->total_pages,
            'char_count' => $document->char_count,
            'status' => $document->status,
            'error_message' => $document->error_message,
            'created_at' => $document->created_at?->diffForHumans(),
            'delete_url' => route('strategic-advisor.documents.destroy', $document),
        ];
    }

    /** Hapus dokumen dari knowledge base (beserta file fisiknya). */
    public function destroyDocument(Request $request, AdvisorDocument $document)
    {
        $this->service->deleteDocument($document);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('strategic-advisor.documents.index')
            ->with('success', 'Dokumen "'.$document->name.'" dihapus dari knowledge base.');
    }

    /**
     * JSON endpoint untuk bertanya / meminta saran dalam sesi chat.
     * Retrieval + Gemini grounded call bisa memakan 15-60 detik.
     */
    public function ask(AskAdvisorRequest $request): JsonResponse
    {
        set_time_limit(300);

        $session = null;
        if ($request->filled('session_id')) {
            $session = AdvisorSession::where('user_id', $request->user()->id)
                ->find($request->input('session_id'));

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'status' => 'failed',
                    'error_message' => 'Sesi tidak ditemukan.',
                ], 404);
            }
        }

        $message = $this->service->ask(
            $request->input('question'),
            $request->user()->id,
            $session,
        );

        return response()->json([
            'success' => $message->status === 'completed',
            'status' => $message->status,
            'message' => $this->messagePayload($message, false),
            'session' => $session ? $this->sessionPayload($session->fresh()) : null,
            'error_message' => $message->error_message,
        ], 200);
    }

    /** Bentuk payload sesi untuk frontend. */
    private function sessionPayload(AdvisorSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'message_count' => $session->message_count,
            'last_activity' => $session->last_activity_at?->diffForHumans()
                ?? $session->created_at?->diffForHumans(),
        ];
    }

    /** Bentuk payload pesan untuk frontend. */
    private function messagePayload(AdvisorMessage $message, bool $pending): array
    {
        return [
            'id' => $message->id,
            'question' => $message->question,
            'answer' => $message->answer,
            'citations' => $message->citations_array,
            'trends' => $message->trends_array,
            'recommendations' => $message->recommendations_array,
            'grounded' => (bool) $message->grounded,
            'status' => $message->status,
            'error_message' => $message->error_message,
            'processing_time' => $message->processing_time !== null
                                    ? (float) $message->processing_time
                                    : null,
            'created_at' => $message->created_at?->diffForHumans(),
            'pending' => $pending,
        ];
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
