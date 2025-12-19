{{-- Author: Liew Zi Li (notification management) --}}
@extends('layouts.app')

@section('title', 'Notifications - TARUMT FMS')

@section('content')
<div class="container">
    <div class="page-header">
        <div class="page-header-content">
            <h1 id="pageTitle">Announcements & Notifications</h1>
            <p id="pageSubtitle">View and manage your announcements and notifications</p>
        </div>
    </div>

    <div class="filters-section" id="searchSection" style="display: none;">
        <div class="filters-card">
            <div class="filters-form">
                <div class="filter-input-wrapper">
                    <div class="filter-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="notificationSearchInput" placeholder="Search by title or content..." class="filter-input">
                    <button type="button" class="filter-clear-btn" id="notificationSearchClear" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="notificationsList" class="table-container">
        <p>Loading...</p>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/notifications/index.css') }}">
<script src="{{ asset('js/notifications/index.js') }}"></script>
@endsection