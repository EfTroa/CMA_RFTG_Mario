@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Customer Management</h5>
                    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Add a Customer
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(empty($customers))
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            No customers available or an error occurred while retrieving data from the API.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Email</th>
                                        <th>Store</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customers as $customer)
                                        <tr>
                                            <td>{{ $customer['customerId'] ?? 'N/A' }}</td>
                                            <td>{{ $customer['firstName'] ?? 'N/A' }}</td>
                                            <td><strong>{{ $customer['lastName'] ?? 'N/A' }}</strong></td>
                                            <td>{{ $customer['email'] ?? 'N/A' }}</td>
                                            <td>{{ $customer['storeId'] ?? 'N/A' }}</td>
                                            <td>
                                                @if($customer['active'] ?? false)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('customers.show', $customer['customerId']) }}"
                                                       class="btn btn-sm btn-info">View</a>
                                                    <a href="{{ route('customers.edit', $customer['customerId']) }}"
                                                       class="btn btn-sm btn-warning">Edit</a>
                                                    <form action="{{ route('customers.destroy', $customer['customerId']) }}"
                                                          method="POST"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this customer?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <p class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Total: <strong>{{ count($customers) }}</strong> customer(s)
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection