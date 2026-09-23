<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $activeItem['title'] ?? 'Email Studio' }} - Email Template Preview</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-canvas: #f1f5f9;
            --sidebar-bg: #0f172a;
            --sidebar-border: #1e293b;
            --sidebar-text: #94a3b8;
            --sidebar-text-active: #ffffff;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --accent-primary: #2563eb;
            --accent-primary-hover: #1d4ed8;
            --accent-success: #10b981;
            --accent-warning: #f59e0b;
            --accent-danger: #ef4444;
            --header-height: 64px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 340px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100vh;
            z-index: 20;
        }

        .sidebar-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-badge {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        .brand-text h1 {
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.3px;
        }

        .brand-text span {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .search-box {
            padding: 14px 18px;
            border-bottom: 1px solid var(--sidebar-border);
        }

        .search-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input-wrap svg {
            position: absolute;
            left: 12px;
            width: 16px;
            height: 16px;
            color: #64748b;
        }

        .search-input {
            width: 100%;
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 9px 12px 9px 36px;
            color: #ffffff;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .type-tabs {
            display: flex;
            padding: 10px 18px;
            gap: 6px;
            background-color: #0b1120;
            border-bottom: 1px solid var(--sidebar-border);
        }

        .type-tab-btn {
            flex: 1;
            padding: 7px 10px;
            background: transparent;
            border: none;
            border-radius: 6px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .type-tab-btn.active {
            background-color: #1e293b;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .type-tab-badge {
            background-color: rgba(255,255,255,0.1);
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 10px;
        }

        .template-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px 12px;
            list-style: none;
        }

        .template-list::-webkit-scrollbar {
            width: 5px;
        }
        .template-list::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }

        .template-item {
            margin-bottom: 4px;
        }

        .template-link {
            display: block;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--sidebar-text);
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .template-link:hover {
            background-color: var(--sidebar-hover);
            color: #e2e8f0;
        }

        .template-link.active {
            background-color: #1e293b;
            color: #ffffff;
            border-left-color: var(--accent-primary);
        }

        .template-link-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .template-link-title {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .category-pill {
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            background-color: #334155;
            color: #cbd5e1;
        }

        .category-pill.client { background-color: #064e3b; color: #6ee7b7; }
        .category-pill.admin { background-color: #451a03; color: #fcd34d; }
        .category-pill.billing { background-color: #1e1b4b; color: #a5b4fc; }
        .category-pill.provisioning { background-color: #172554; color: #93c5fd; }
        .category-pill.blade { background-color: #3b0764; color: #e9d5ff; }

        .template-link-meta {
            font-size: 11px;
            color: #64748b;
            font-family: 'JetBrains Mono', monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Main Workspace Styles */
        .workspace {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            background-color: #f8fafc;
        }

        .top-navbar {
            height: var(--header-height);
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            flex-shrink: 0;
            z-index: 10;
        }

        .active-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .active-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .active-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .active-subject-badge {
            font-size: 12px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .active-subject-badge strong {
            color: var(--text-primary);
        }

        .viewport-controls {
            display: flex;
            background-color: #f1f5f9;
            padding: 3px;
            border-radius: 8px;
            gap: 3px;
        }

        .viewport-btn {
            background: transparent;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }

        .viewport-btn svg {
            width: 15px;
            height: 15px;
        }

        .viewport-btn.active {
            background-color: #ffffff;
            color: var(--accent-primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
        }

        .btn svg {
            width: 15px;
            height: 15px;
        }

        .btn-outline {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-primary {
            background-color: var(--accent-primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--accent-primary-hover);
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
        }

        .btn-success {
            background-color: var(--accent-success);
            color: #ffffff;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        /* Workspace Content: Preview + Inspector */
        .workspace-body {
            flex: 1;
            display: flex;
            overflow: hidden;
            position: relative;
        }

        /* Center Canvas */
        .canvas-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 24px;
            overflow-y: auto;
            background: #f1f5f9 radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
            transition: all 0.3s ease;
        }

        .preview-frame-container {
            width: 100%;
            max-width: 100%;
            height: 100%;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid #e2e8f0;
            transition: max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .preview-frame-container.viewport-desktop {
            max-width: 100%;
        }

        .preview-frame-container.viewport-tablet {
            max-width: 768px;
        }

        .preview-frame-container.viewport-mobile {
            max-width: 375px;
        }

        .frame-header {
            height: 38px;
            background-color: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 14px;
            flex-shrink: 0;
        }

        .window-dots {
            display: flex;
            gap: 6px;
        }

        .window-dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .window-dots .red { background-color: #f87171; }
        .window-dots .yellow { background-color: #fbbf24; }
        .window-dots .green { background-color: #34d399; }

        .frame-url-bar {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 3px 12px;
            font-size: 11px;
            color: var(--text-secondary);
            font-family: 'JetBrains Mono', monospace;
            max-width: 400px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .preview-iframe {
            flex: 1;
            width: 100%;
            height: 100%;
            border: none;
            background-color: #ffffff;
        }

        /* Right Inspector Drawer */
        .inspector {
            width: 440px;
            background-color: #ffffff;
            border-left: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100%;
            transition: margin-right 0.3s ease;
            z-index: 10;
        }

        .inspector.collapsed {
            margin-right: -440px;
        }

        .inspector-nav {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            background-color: #f8fafc;
        }

        .inspector-tab {
            flex: 1;
            padding: 14px 10px;
            border: none;
            background: transparent;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .inspector-tab svg {
            width: 15px;
            height: 15px;
        }

        .inspector-tab.active {
            color: var(--accent-primary);
            border-bottom-color: var(--accent-primary);
            background-color: #ffffff;
        }

        .inspector-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .section-header {
            margin-bottom: 16px;
        }

        .section-header h3 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .section-header p {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .var-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .var-table th {
            text-align: left;
            padding: 8px 10px;
            background-color: #f8fafc;
            color: var(--text-secondary);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
        }

        .var-table td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .var-name {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: #2563eb;
            background-color: #eff6ff;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            cursor: pointer;
        }

        .var-name:hover {
            background-color: #dbeafe;
        }

        .var-desc {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        .var-type {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            background: #f1f5f9;
            padding: 1px 5px;
            border-radius: 3px;
        }

        /* JSON / Form Editor */
        .editor-container {
            margin-top: 14px;
        }

        .json-editor-textarea {
            width: 100%;
            height: 240px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: #f8fafc;
            color: #1e293b;
            line-height: 1.5;
            outline: none;
            resize: vertical;
        }

        .json-editor-textarea:focus {
            border-color: var(--accent-primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .html-editor-textarea {
            width: 100%;
            height: 380px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: #f8fafc;
            color: #1e293b;
            line-height: 1.5;
            outline: none;
            resize: vertical;
        }

        .html-editor-textarea:focus {
            border-color: var(--accent-primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            font-size: 13px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            outline: none;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .tag-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: 10px 0 16px 0;
        }

        .tag-chip {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            background-color: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            padding: 3px 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .tag-chip:hover {
            background-color: #2563eb;
            color: white;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: #ffffff;
            width: 480px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            animation: modalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalPop {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            font-size: 15px;
            font-weight: 700;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 14px 20px;
            background-color: #f8fafc;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 200;
        }

        .toast {
            background-color: #1e293b;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: toastIn 0.2s ease-out;
            border-left: 4px solid var(--accent-primary);
        }

        .toast.success { border-left-color: var(--accent-success); }
        .toast.error { border-left-color: var(--accent-danger); }

        @keyframes toastIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .code-pre {
            background-color: #0f172a;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            line-height: 1.5;
            overflow-x: auto;
            max-height: 480px;
        }
    </style>
</head>
<body>

    <!-- Left Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand-badge">
                <div class="brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div class="brand-text">
                    <h1>TidCraft Mail Studio</h1>
                    <span>Template Inspector</span>
                </div>
            </div>
        </div>

        <div class="search-box">
            <div class="search-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="searchTemplates" class="search-input" placeholder="Search templates (Press '/' to focus)..." autofocus>
            </div>
        </div>

        <div class="type-tabs">
            <button class="type-tab-btn active" data-type-filter="all">
                All <span class="type-tab-badge">{{ count($dbTemplates) + count($mailables) }}</span>
            </button>
            <button class="type-tab-btn" data-type-filter="template">
                Dynamic DB <span class="type-tab-badge">{{ count($dbTemplates) }}</span>
            </button>
            <button class="type-tab-btn" data-type-filter="mailable">
                Blade Views <span class="type-tab-badge">{{ count($mailables) }}</span>
            </button>
        </div>

        <ul class="template-list" id="templateList">
            <!-- Dynamic Database Templates -->
            @foreach($dbTemplates as $item)
                @php
                    $isActive = ($activeItem['type'] === 'template' && $activeItem['id'] == $item['id']);
                    $catClass = strtolower(explode(' ', $item['category'])[0]);
                @endphp
                <li class="template-item" data-type="template" data-category="{{ strtolower($item['category']) }}" data-title="{{ strtolower($item['title']) }}" data-slug="{{ strtolower($item['slug']) }}">
                    <a href="{{ route('email-previews.index', ['type' => 'template', 'id' => $item['id']]) }}" class="template-link {{ $isActive ? 'active' : '' }}">
                        <div class="template-link-header">
                            <span class="template-link-title">{{ $item['title'] }}</span>
                            <span class="category-pill {{ $catClass }}">{{ $item['category'] }}</span>
                        </div>
                        <div class="template-link-meta">
                            slug: {{ $item['slug'] }}
                        </div>
                    </a>
                </li>
            @endforeach

            <!-- Blade Mailables -->
            @foreach($mailables as $item)
                @php
                    $isActive = ($activeItem['type'] === 'mailable' && $activeItem['id'] === $item['id']);
                @endphp
                <li class="template-item" data-type="mailable" data-category="{{ strtolower($item['category']) }}" data-title="{{ strtolower($item['title']) }}" data-slug="{{ strtolower($item['id']) }}">
                    <a href="{{ route('email-previews.index', ['type' => 'mailable', 'id' => $item['id']]) }}" class="template-link {{ $isActive ? 'active' : '' }}">
                        <div class="template-link-header">
                            <span class="template-link-title">{{ $item['title'] }}</span>
                            <span class="category-pill blade">Blade</span>
                        </div>
                        <div class="template-link-meta">
                            {{ $item['view'] }}
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </aside>

    <!-- Main Workspace -->
    <main class="workspace">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="active-meta">
                <div class="active-title-row">
                    <span class="active-title">{{ $activeItem['title'] ?? 'Email Template' }}</span>
                    <span class="category-pill {{ $activeItem['type'] === 'template' ? 'client' : 'blade' }}">
                        {{ $activeItem['type'] === 'template' ? 'Database Dynamic Template' : 'Blade Mailable' }}
                    </span>
                    @if(isset($activeItem['status']) && $activeItem['status'] === 'active')
                        <span style="font-size: 11px; color: var(--accent-success); font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--accent-success);"></span> Active
                        </span>
                    @endif
                </div>
                <div class="active-subject-badge">
                    <span>Subject:</span>
                    <strong id="activeSubjectPreview">{{ $activeItem['subject'] ?? 'No subject specified' }}</strong>
                    <button type="button" onclick="copySubject()" style="background:none; border:none; color:var(--text-secondary); cursor:pointer; padding:2px;" title="Copy Subject">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Viewport Switcher -->
            <div class="viewport-controls">
                <button class="viewport-btn active" data-viewport="desktop" title="Full Width Desktop View">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    Desktop
                </button>
                <button class="viewport-btn" data-viewport="tablet" title="Tablet View (768px)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                    </svg>
                    Tablet
                </button>
                <button class="viewport-btn" data-viewport="mobile" title="Mobile Phone View (375px)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                    </svg>
                    Mobile
                </button>
            </div>

            <!-- Top Actions -->
            <div class="top-actions">
                <button class="btn btn-outline" id="btnRefreshPreview" title="Reload iframe preview">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    Refresh
                </button>
                <a href="{{ route('email-previews.render', ['type' => $activeItem['type'], 'id' => $activeItem['id']]) }}" target="_blank" class="btn btn-outline" title="Open full email in a new tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    Open New Tab
                </a>
                <button class="btn btn-primary" onclick="openTestModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    Send Test
                </button>
                <button class="btn btn-outline" id="toggleInspectorBtn" title="Toggle Side Inspector Panel">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                    Inspector
                </button>
            </div>
        </header>

        <!-- Workspace Body -->
        <div class="workspace-body">
            <!-- Center Canvas -->
            <div class="canvas-area">
                <div class="preview-frame-container viewport-desktop" id="frameContainer">
                    <div class="frame-header">
                        <div class="window-dots">
                            <span class="red"></span>
                            <span class="yellow"></span>
                            <span class="green"></span>
                        </div>
                        <div class="frame-url-bar" id="frameUrlLabel">
                            preview://{{ $activeItem['slug'] ?? $activeItem['id'] }}
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted); font-weight: 500;" id="viewportSizeLabel">
                            100% Responsive
                        </div>
                    </div>
                    <iframe id="previewIframe" class="preview-iframe" src="{{ route('email-previews.render', ['type' => $activeItem['type'], 'id' => $activeItem['id']]) }}"></iframe>
                </div>
            </div>

            <!-- Right Inspector Drawer -->
            <aside class="inspector" id="inspectorDrawer">
                <div class="inspector-nav">
                    <button class="inspector-tab active" data-tab="tab-variables">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        Variables & Data
                    </button>
                    @if($activeItem['type'] === 'template')
                    <button class="inspector-tab" data-tab="tab-edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit Template
                    </button>
                    @endif
                    <button class="inspector-tab" data-tab="tab-code">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                        Source
                    </button>
                </div>

                <div class="inspector-body">
                    <!-- Tab 1: Variables & Data -->
                    <div class="tab-pane active" id="tab-variables">
                        <div class="section-header">
                            <h3>Variables Sent to this Email</h3>
                            <p>Here are the data variables and placeholders expected by this template:</p>
                        </div>

                        <table class="var-table">
                            <thead>
                                <tr>
                                    <th>Variable / Key</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeItem['variables'] ?? [] as $varKey => $varInfo)
                                    <tr>
                                        <td>
                                            <span class="var-name" onclick="insertVariable('{{ $varKey }}')" title="Click to copy variable">{{ $varKey }}</span>
                                        </td>
                                        <td>
                                            <span class="var-type">{{ $varInfo['type'] ?? 'string' }}</span>
                                        </td>
                                        <td class="var-desc">
                                            {{ $varInfo['desc'] ?? '' }}
                                            @if(isset($varInfo['example']))
                                                <div style="font-size: 10px; color: #94a3b8; margin-top: 2px;">
                                                    Ex: <code>{{ $varInfo['example'] }}</code>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 15px;">
                                            No explicit variables registered.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="section-header">
                            <h3>Interactive Sample Data Editor</h3>
                            <p>Tweak the sample JSON data below and click "Apply & Refresh" to test different values in real-time:</p>
                        </div>

                        <div class="editor-container">
                            <textarea id="sampleDataJson" class="json-editor-textarea" spellcheck="false">{{ json_encode($activeItem['default_data'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                            <div style="display: flex; gap: 8px; margin-top: 10px;">
                                <button type="button" class="btn btn-primary" style="flex: 1;" onclick="applyCustomData()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    Apply & Refresh Preview
                                </button>
                                <button type="button" class="btn btn-outline" onclick="resetDefaultData()">
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Edit Template (Database Templates) -->
                    @if($activeItem['type'] === 'template')
                    <div class="tab-pane" id="tab-edit">
                        <div class="section-header">
                            <h3>Edit Email Template</h3>
                            <p>Update the subject line and HTML/Blade content directly in the database:</p>
                        </div>

                        <form id="editTemplateForm" onsubmit="saveTemplateChanges(event)">
                            <div class="form-group">
                                <label for="tplTitle">Template Title</label>
                                <input type="text" id="tplTitle" class="form-control" value="{{ $activeItem['title'] }}">
                            </div>

                            <div class="form-group">
                                <label for="tplSubject">Email Subject</label>
                                <input type="text" id="tplSubject" class="form-control" value="{{ $activeItem['subject'] }}">
                            </div>

                            <div class="form-group">
                                <label>Click to Insert Variable Tag</label>
                                <div class="tag-cloud">
                                    @foreach(array_keys($activeItem['variables'] ?? []) as $tag)
                                        <span class="tag-chip" onclick="insertTagToEditor('{{ $tag }}')">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="tplContent">HTML & Blade Content</label>
                                <textarea id="tplContent" class="html-editor-textarea" spellcheck="false">{{ $activeItem['content'] ?? '' }}</textarea>
                            </div>

                            <div style="display: flex; gap: 8px; margin-top: 14px;">
                                <button type="submit" class="btn btn-success" style="flex: 1;" id="btnSaveTemplate">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                        <polyline points="7 3 7 8 15 8"></polyline>
                                    </svg>
                                    Save Changes to Database
                                </button>
                                <button type="button" class="btn btn-outline" onclick="previewLiveEditor()">
                                    Test Render
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif

                    <!-- Tab 3: Source Code -->
                    <div class="tab-pane" id="tab-code">
                        <div class="section-header">
                            <h3>Template Source</h3>
                            <p>
                                @if($activeItem['type'] === 'template')
                                    Database Template (Slug: <code>{{ $activeItem['slug'] }}</code>)
                                @else
                                    Blade Template View: <code>{{ $activeItem['view'] ?? '' }}</code>
                                @endif
                            </p>
                        </div>

                        <div style="margin-bottom: 12px; display: flex; justify-content: flex-end;">
                            <button type="button" class="btn btn-outline" onclick="copySourceCode()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                Copy Source
                            </button>
                        </div>

                        <pre class="code-pre" id="sourceCodeBlock">{{ $activeItem['content'] ?? 'View file: resources/views/' . str_replace('.', '/', $activeItem['view'] ?? '') . '.blade.php' }}</pre>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Test Email Modal -->
    <div class="modal-overlay" id="testModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Send Test Email</h3>
                <button type="button" onclick="closeTestModal()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:18px;">&times;</button>
            </div>
            <form onsubmit="handleSendTest(event)">
                <div class="modal-body">
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
                        Sends a live email preview of <strong>{{ $activeItem['title'] }}</strong> using your active SMTP mail configuration.
                    </p>
                    <div class="form-group">
                        <label for="testRecipientEmail">Recipient Email Address</label>
                        <input type="email" id="testRecipientEmail" class="form-control" placeholder="your.email@example.com" required>
                    </div>
                    <div id="testEmailStatus" style="display:none; font-size:12px; padding:10px; border-radius:6px; margin-top:10px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeTestModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitTest">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        Send Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        const activeItem = @json($activeItem);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const defaultSampleData = @json($activeItem['default_data'] ?? []);

        // Viewport Switcher
        document.querySelectorAll('.viewport-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.viewport-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const viewport = this.dataset.viewport;
                const container = document.getElementById('frameContainer');
                container.className = 'preview-frame-container viewport-' + viewport;
                
                const label = document.getElementById('viewportSizeLabel');
                if (viewport === 'desktop') label.textContent = '100% Responsive';
                if (viewport === 'tablet') label.textContent = '768px (Tablet)';
                if (viewport === 'mobile') label.textContent = '375px (Mobile)';
            });
        });

        // Inspector Tabs
        document.querySelectorAll('.inspector-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.inspector-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                const target = document.getElementById(this.dataset.tab);
                if (target) target.classList.add('active');
            });
        });

        // Toggle Inspector Drawer
        document.getElementById('toggleInspectorBtn').addEventListener('click', function () {
            document.getElementById('inspectorDrawer').classList.toggle('collapsed');
        });

        // Refresh Preview
        document.getElementById('btnRefreshPreview').addEventListener('click', function () {
            const iframe = document.getElementById('previewIframe');
            iframe.src = iframe.src;
            showToast('Preview refreshed', 'info');
        });

        // Search Filter
        const searchInput = document.getElementById('searchTemplates');
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            filterTemplates();
        });

        // Focus search on '/' key
        document.addEventListener('keydown', function (e) {
            if (e.key === '/' && document.activeElement !== searchInput && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                searchInput.focus();
            }
        });

        // Type Filter Tabs
        let currentTypeFilter = 'all';
        document.querySelectorAll('.type-tab-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.type-tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentTypeFilter = this.dataset.typeFilter;
                filterTemplates();
            });
        });

        function filterTemplates() {
            const query = searchInput.value.toLowerCase().trim();
            document.querySelectorAll('.template-item').forEach(item => {
                const type = item.dataset.type;
                const title = item.dataset.title;
                const slug = item.dataset.slug;
                const category = item.dataset.category;

                const matchesType = (currentTypeFilter === 'all' || type === currentTypeFilter);
                const matchesQuery = !query || title.includes(query) || slug.includes(query) || category.includes(query);

                if (matchesType && matchesQuery) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Apply Custom Data to Preview
        function applyCustomData() {
            const rawJson = document.getElementById('sampleDataJson').value;
            let parsedData;
            try {
                parsedData = JSON.parse(rawJson);
            } catch (err) {
                showToast('Invalid JSON: ' + err.message, 'error');
                return;
            }

            // Post to render endpoint and update iframe
            fetch('{{ route("email-previews.render") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    type: activeItem.type,
                    id: activeItem.id,
                    data: parsedData,
                    format: 'json'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const iframe = document.getElementById('previewIframe');
                    const doc = iframe.contentWindow.document;
                    doc.open();
                    doc.write(data.html);
                    doc.close();

                    if (data.subject) {
                        document.getElementById('activeSubjectPreview').textContent = data.subject;
                    }
                    showToast('Preview updated with custom data!', 'success');
                } else {
                    showToast(data.message || 'Render failed', 'error');
                }
            })
            .catch(err => {
                showToast('Error applying data: ' + err.message, 'error');
            });
        }

        // Reset Default Sample Data
        function resetDefaultData() {
            document.getElementById('sampleDataJson').value = JSON.stringify(defaultSampleData, null, 4);
            applyCustomData();
        }

        // Insert Variable tag into HTML editor
        function insertTagToEditor(tag) {
            const textarea = document.getElementById('tplContent');
            if (!textarea) return;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.substring(0, start) + tag + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + tag.length;
            showToast('Inserted ' + tag, 'info');
        }

        // Copy variable
        function insertVariable(name) {
            navigator.clipboard.writeText(name).then(() => {
                showToast('Copied ' + name + ' to clipboard', 'info');
            });
        }

        function copySubject() {
            const subject = document.getElementById('activeSubjectPreview').textContent;
            navigator.clipboard.writeText(subject).then(() => {
                showToast('Subject copied to clipboard', 'info');
            });
        }

        function copySourceCode() {
            const code = document.getElementById('sourceCodeBlock').textContent;
            navigator.clipboard.writeText(code).then(() => {
                showToast('Source copied to clipboard', 'info');
            });
        }

        // Live test render from template editor
        function previewLiveEditor() {
            const content = document.getElementById('tplContent').value;
            const subject = document.getElementById('tplSubject').value;
            const rawJson = document.getElementById('sampleDataJson').value;
            let customData = {};
            try { customData = JSON.parse(rawJson); } catch (e) {}

            fetch('{{ route("email-previews.render") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    type: activeItem.type,
                    id: activeItem.id,
                    data: customData,
                    override_content: content,
                    override_subject: subject,
                    format: 'json'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const iframe = document.getElementById('previewIframe');
                    const doc = iframe.contentWindow.document;
                    doc.open();
                    doc.write(data.html);
                    doc.close();
                    if (data.subject) {
                        document.getElementById('activeSubjectPreview').textContent = data.subject;
                    }
                    showToast('Rendered live edits in preview!', 'info');
                } else {
                    showToast('Render error: ' + data.message, 'error');
                }
            });
        }

        // Save Template Changes (Database)
        function saveTemplateChanges(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveTemplate');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Saving...';

            const payload = {
                title: document.getElementById('tplTitle').value,
                subject: document.getElementById('tplSubject').value,
                content: document.getElementById('tplContent').value,
            };

            fetch(`/email-previews/update/${activeItem.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (data.success) {
                    showToast(data.message, 'success');
                    document.getElementById('activeSubjectPreview').textContent = payload.subject;
                    // Refresh iframe with saved content
                    applyCustomData();
                } else {
                    showToast(data.message || 'Failed to update template', 'error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                showToast('Save error: ' + err.message, 'error');
            });
        }

        // Modal Controls
        function openTestModal() {
            document.getElementById('testModal').classList.add('active');
            document.getElementById('testEmailStatus').style.display = 'none';
            document.getElementById('testRecipientEmail').focus();
        }

        function closeTestModal() {
            document.getElementById('testModal').classList.remove('active');
        }

        // Send Test Email
        function handleSendTest(e) {
            e.preventDefault();
            const email = document.getElementById('testRecipientEmail').value;
            const btn = document.getElementById('btnSubmitTest');
            const statusDiv = document.getElementById('testEmailStatus');

            btn.disabled = true;
            btn.innerHTML = 'Sending...';
            statusDiv.style.display = 'none';

            let customData = {};
            try { customData = JSON.parse(document.getElementById('sampleDataJson').value); } catch (e) {}

            fetch('{{ route("email-previews.send-test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    type: activeItem.type,
                    id: activeItem.id,
                    email: email,
                    data: customData
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = 'Send Now';
                statusDiv.style.display = 'block';

                if (data.success) {
                    statusDiv.style.background = '#d1fae5';
                    statusDiv.style.color = '#065f46';
                    statusDiv.innerHTML = '<strong>Success!</strong> ' + data.message;
                    showToast(data.message, 'success');
                    setTimeout(closeTestModal, 2000);
                } else {
                    statusDiv.style.background = '#fee2e2';
                    statusDiv.style.color = '#991b1b';
                    statusDiv.innerHTML = '<strong>Error:</strong> ' + data.message;
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = 'Send Now';
                statusDiv.style.display = 'block';
                statusDiv.style.background = '#fee2e2';
                statusDiv.style.color = '#991b1b';
                statusDiv.innerHTML = '<strong>Network Error:</strong> ' + err.message;
                showToast(err.message, 'error');
            });
        }

        // Toast Helper
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast ' + type;
            toast.textContent = message;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all 0.2s';
                setTimeout(() => toast.remove(), 200);
            }, 3000);
        }
    </script>
</body>
</html>
