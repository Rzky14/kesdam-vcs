@extends('layouts.app')

@section('title', 'Reject Document - ' . $document->subject)

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h2">Reject Document</h1>
            <p class="text-muted">{{ $document->subject }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Rejection Form</h5>
                </div>
                <form action="{{ route('approvals.reject', $document) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" 
                                      id="reason" name="reason" rows="4" required></textarea>
                            @error('reason')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3"></textarea>
                            @error('notes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('approvals.show', $document) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times"></i> Reject Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
