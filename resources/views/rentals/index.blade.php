@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Rental Management</h5>
                </div>

                <div class="card-body">
                    @if(empty($rentals))
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            No rentals available or an error occurred while retrieving data from the API.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Rental Date</th>
                                        <th>Customer (ID)</th>
                                        <th>Inventory (ID)</th>
                                        <th>Staff (ID)</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rentals as $rental)
                                        <tr>
                                            <td>{{ $rental['rentalId'] ?? 'N/A' }}</td>
                                            <td>{{ $rental['rentalDate'] ?? 'N/A' }}</td>
                                            <td>{{ $rental['customerId'] ?? 'N/A' }}</td>
                                            <td>{{ $rental['inventoryId'] ?? 'N/A' }}</td>
                                            <td>{{ $rental['staffId'] ?? 'N/A' }}</td>
                                            <td>{{ $rental['returnDate'] ?? '—' }}</td>
                                            <td>
                                                @php $sid = $rental['statusId'] ?? null; @endphp
                                                @if($sid == 3)
                                                    <span class="badge bg-primary">In Progress</span>
                                                @elseif($sid == 2)
                                                    <span class="badge bg-warning text-dark">In Cart</span>
                                                @elseif($sid == 1)
                                                    <span class="badge bg-success">Completed</span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <form action="{{ route('rentals.updateStatus', $rental['rentalId']) }}"
                                                      method="POST"
                                                      class="d-flex gap-1 align-items-center">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="rentalDate"  value="{{ $rental['rentalDate'] ?? '' }}">
                                                    <input type="hidden" name="inventoryId" value="{{ $rental['inventoryId'] ?? '' }}">
                                                    <input type="hidden" name="customerId"  value="{{ $rental['customerId'] ?? '' }}">
                                                    <input type="hidden" name="staffId"     value="{{ $rental['staffId'] ?? '' }}">
                                                    <input type="hidden" name="returnDate"  value="{{ $rental['returnDate'] ?? '' }}">
                                                    <select name="statusId" class="form-select form-select-sm" style="width:auto">
                                                        @foreach($statuses as $sid => $label)
                                                            <option value="{{ $sid }}"
                                                                {{ ($rental['statusId'] ?? null) == $sid ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary">OK</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <p class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Total: <strong>{{ count($rentals) }}</strong> rental(s)
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection