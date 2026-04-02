@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Customer Details</h5>
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <h3>{{ $customer['firstName'] ?? '' }} {{ $customer['lastName'] ?? '' }}</h3>
                            <p class="text-muted">{{ $customer['email'] ?? '' }}</p>
                        </div>
                        <div class="col-md-4 text-end">
                            @if($customer['active'] ?? false)
                                <span class="badge bg-success fs-6">Active</span>
                            @else
                                <span class="badge bg-secondary fs-6">Inactive</span>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <dl class="row">
                        <dt class="col-sm-3">ID</dt>
                        <dd class="col-sm-9">{{ $customer['customerId'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">First Name</dt>
                        <dd class="col-sm-9">{{ $customer['firstName'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Last Name</dt>
                        <dd class="col-sm-9">{{ $customer['lastName'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Email</dt>
                        <dd class="col-sm-9">{{ $customer['email'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Store (ID)</dt>
                        <dd class="col-sm-9">{{ $customer['storeId'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Address (ID)</dt>
                        <dd class="col-sm-9">{{ $customer['addressId'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Created On</dt>
                        <dd class="col-sm-9">{{ $customer['createDate'] ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Last Updated</dt>
                        <dd class="col-sm-9">{{ $customer['lastUpdate'] ?? 'N/A' }}</dd>
                    </dl>

                    <hr>

                    <div class="d-flex gap-2">
                        <a href="{{ route('customers.edit', $customer['customerId']) }}" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('customers.destroy', $customer['customerId']) }}"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('Are you sure you want to delete this customer?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection