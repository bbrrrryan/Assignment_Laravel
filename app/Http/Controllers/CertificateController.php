<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    /**
     * Download a certificate as PDF for the authenticated user.
     *
     * NOTE: Requires barryvdh/laravel-dompdf:
     *   composer require barryvdh/laravel-dompdf
     */
    public function download($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $certificate = Certificate::with(['user', 'reward'])->findOrFail($id);

        // Only owner or admin can download
        if ($certificate->user_id !== $user->id && !$user->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'reward' => $certificate->reward,
        ])->setPaper('A4', 'portrait');

        $fileName = 'certificate-' . ($certificate->certificate_number ?? 'TARUMT') . '.pdf';

        return $pdf->download($fileName);
    }
}


