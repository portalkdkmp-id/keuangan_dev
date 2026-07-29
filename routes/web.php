<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CooperativeAssignmentController;
use App\Http\Controllers\CooperativeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceSubmissionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SubmissionAttachmentController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('cooperatives/import', [CooperativeController::class, 'importForm'])->name('cooperatives.import');
    Route::post('cooperatives/import', [CooperativeController::class, 'import'])->name('cooperatives.import.store');
    Route::resource('cooperatives', CooperativeController::class);
    Route::post('cooperatives/{cooperative}/pics', [CooperativeAssignmentController::class, 'store'])->name('cooperatives.pics.store');
    Route::delete('cooperatives/{cooperative}/pics/{user}', [CooperativeAssignmentController::class, 'destroy'])->name('cooperatives.pics.destroy');
    Route::patch('cooperatives/{cooperative}/pics/{user}/primary', [CooperativeAssignmentController::class, 'primary'])->name('cooperatives.pics.primary');
    Route::get('regions/provinces', [RegionController::class, 'provinces'])->name('regions.provinces');
    Route::get('regions/cities', [RegionController::class, 'cities'])->name('regions.cities');
    Route::get('regions/districts', [RegionController::class, 'districts'])->name('regions.districts');
    Route::get('regions/villages', [RegionController::class, 'villages'])->name('regions.villages');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/create', [SubmissionController::class, 'create'])->name('submissions.create');
    Route::post('/submissions', [SubmissionController::class, 'store'])->name('submissions.store');
    Route::get('/submissions/{financialSubmission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/submissions/{financialSubmission}/edit', [SubmissionController::class, 'edit'])->name('submissions.edit');
    Route::put('/submissions/{financialSubmission}', [SubmissionController::class, 'update'])->name('submissions.update');
    Route::delete('/submissions/{financialSubmission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
    Route::post('/submissions/{financialSubmission}/submit', [SubmissionController::class, 'submit'])->name('submissions.submit');
    Route::post('/submissions/{financialSubmission}/cancel', [SubmissionController::class, 'cancel'])->name('submissions.cancel');
    Route::post('/submissions/{financialSubmission}/attachments', [SubmissionAttachmentController::class, 'store'])->name('submissions.attachments.store');
    Route::delete('/submission-attachments/{submissionAttachment}', [SubmissionAttachmentController::class, 'destroy'])->name('submission-attachments.destroy');
    Route::get('/submission-attachments/{submissionAttachment}/download', [SubmissionAttachmentController::class, 'download'])->name('submission-attachments.download');
    Route::get('/finance/submissions', [FinanceSubmissionController::class, 'index'])->name('finance.submissions.index');
    Route::get('/finance/submissions/{financialSubmission}', [FinanceSubmissionController::class, 'show'])->name('finance.submissions.show');
    Route::post('/finance/submissions/{financialSubmission}/start-review', [FinanceSubmissionController::class, 'startReview'])->name('finance.submissions.start-review');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__.'/settings.php';
