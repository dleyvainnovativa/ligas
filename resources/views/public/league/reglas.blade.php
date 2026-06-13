@extends('layouts.public')
@section('title', "Reglas — {$league->name}")

@section('content')
<section class="public-section">
    <h2 class="mb-4">Reglas de la liga</h2>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="public-rule-card">
                <h6><i class="fa-solid fa-trophy text-warning me-2"></i> Formato</h6>
                <ul class="list-unstyled mb-0">
                    <li><strong>{{ $payload['format'] === 'pairs' ? 'Parejas' : 'Individual' }}</strong></li>
                    <li>{{ $payload['num_jornadas'] }} jornadas en total</li>
                    @if ($payload['cost'])
                    <li>Costo por jugador: ${{ number_format($payload['cost'], 0) }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-md-6">
            <div class="public-rule-card">
                <h6><i class="fa-solid fa-calculator text-primary me-2"></i> Sistema de puntos</h6>
                <ul class="list-unstyled mb-0">
                    <li>Victoria: <strong>{{ $payload['points']['win'] }}</strong></li>
                    <li>Empate: <strong>{{ $payload['points']['draw'] }}</strong></li>
                    <li>Derrota: <strong>{{ $payload['points']['loss'] }}</strong></li>
                </ul>
            </div>
        </div>

        <div class="col-md-6">
            <div class="public-rule-card">
                <h6><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Penalizaciones</h6>
                <ul class="list-unstyled mb-0">
                    <li>No-show: <strong>−{{ $payload['penalties']['no_show'] }}</strong></li>
                    <li>Suplente: <strong>−{{ $payload['penalties']['suplente'] }}</strong></li>
                </ul>
            </div>
        </div>

        <div class="col-md-6">
            <div class="public-rule-card">
                <h6><i class="fa-solid fa-clock text-info me-2"></i> Calendario</h6>
                <ul class="list-unstyled mb-0">
                    <li><strong>Días:</strong> {{ implode(', ', $payload['schedule']['days']) }}</li>
                    <li><strong>Horarios:</strong> {{ implode(', ', $payload['schedule']['time_slots']) }}</li>
                </ul>
            </div>
        </div>

        <div class="col-12">
            <div class="public-rule-card">
                <h6><i class="fa-solid fa-location-dot text-success me-2"></i> Sedes y pistas</h6>
                @if (empty($payload['sedes']))
                <p class="text-muted mb-0">No hay sedes registradas.</p>
                @else
                @foreach ($payload['sedes'] as $sede)
                <div class="public-sede-block">
                    <strong>{{ $sede['name'] }}</strong>
                    @if ($sede['address'])
                    <small class="text-muted d-block">{{ $sede['address'] }}</small>
                    @endif
                    @if (!empty($sede['pistas']))
                    <small class="text-secondary">
                        Pistas: {{ implode(', ', $sede['pistas']) }}
                    </small>
                    @endif
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>
</section>
@endsection