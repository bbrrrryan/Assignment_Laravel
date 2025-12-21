/**
 * Author: Ng Jhun Hou
 */ 
@extends('layouts.app')

@section('title', 'Facilities - TARUMT FMS')


@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Facilities</h1>
            <p>Browse and book available facilities</p>
        </div>
    </div>

    <div class="filters-section">
        <div class="filters-card">
            <form class="filters-form" id="filterForm">
                <div class="filter-input-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="searchInput" placeholder="Search by name, code or location..." 
                           class="filter-input" onkeyup="filterFacilities()">
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <select id="typeFilter" class="filter-select" onchange="filterFacilities()">
                        <option value="">All Types</option>
                    </select>
                </div>
                
                <div class="filter-select-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <select id="statusFilter" class="filter-select" onchange="filterFacilities()">
                        <option value="">All Status</option>
                        <option value="available">Available</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                
                <button type="button" class="btn-search" onclick="filterFacilities()">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </button>
                
                <button type="button" class="btn-clear" onclick="clearFilters()">
                    <i class="fas fa-times"></i>
                    <span>Clear</span>
                </button>
            </form>
        </div>
    </div>

    <div id="facilitiesList" class="facilities-grid">
        <p>Loading facilities...</p>
    </div>
    
</div>

<link rel="stylesheet" href="{{ asset('css/facilities/index.css') }}">
<script src="{{ asset('js/facilities/index.js') }}"></script>
@endsection
