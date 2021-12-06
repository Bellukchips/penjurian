@extends('admin.dashboard')
@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">{{ Breadcrumbs::render('dataEvents'); }}</h1>
            <a href="{{ route('manageEvents.create') }}" class="btn btn-primary">Tambah Data</a>
        </div>
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('eventFailed'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('eventFailed') }}
            </div>
        @endif
        <div class="card-body mb-5">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%">
                    <thead>
                        <th>#</th>
                        <th>Nama Acara</th>
                        <th>Penanggung Jawab</th>
                        <th>Alamat Acara</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Berakhir</th>
                        <th>Token</th>
                        <th>Edit</th>
                        <th>Hapus</th>
                    </thead>
                    <tbody>
                        @forelse ($event as $index => $item)
                            <tr>
                                <td class="border px-6 py-4">{{ $index + 1 }}</td>
                                <td class="border px-6 py-4">{{ $item->name }}</td>
                                <td class="border px-6 py-4">{{ $item->name_person_responsible }}</td>
                                <td class="border px-6 py-4">{{ $item->address }}</td>
                                <td class="border px-6 py-4">{{ $item->start_date }}</td>
                                <td class="border px-6 py-4">{{ $item->end_date }}</td>
                                <td class="border px-6 py-4">{{ $item->token }}</td>
                                <td class="border px-6 py-4">
                                    <a href="{{ route('manageEvents.edit', $item->id) }}" style="text-decoration:none;"
                                        class="btn btn-outline-warning">
                                        Edit
                                    </a>
                                </td>
                                <td class="border px-6 py-4">
                                    <form action="{{ route('manageEvents.destroy', $item->id) }}" method="POST">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-danger">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <td colspan="9" class="border px-6 py-4 text-center">Tidak ada data</td>

                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
