@extends('layouts.app')

@section('title', 'Request Correction - ' . $document->subject)

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h2">Request Correction</h1>
            <p class="text-muted">{{ $document->subject }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Correction Request Form</h5>
                </div>
                <form action="{{ route('approvals.requestCorrection', $document) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label">Required Changes <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="4" required
                                      placeholder="Describe what corrections are needed..."></textarea>
                            @error('notes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date (Optional)</label>
                            <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                   id="due_date" name="due_date">
                            @error('due_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">If not specified, default is 3 days from now</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('approvals.show', $document) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Send Correction Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
