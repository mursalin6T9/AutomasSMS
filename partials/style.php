<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

?>
<style>
    .automassms-wrapper {
        --am-brand: #1f8a70;
        --am-brand-dark: #176b57;
        --am-ink: #26313a;
        --am-muted: #6b7a86;
        --am-line: #dfe5ea;
        --am-bg: #f6f8f9;
        --am-radius: 6px;

        color: var(--am-ink);
        font-size: 13px;
    }

    .automassms-wrapper *,
    .automassms-wrapper *::before,
    .automassms-wrapper *::after {
        box-sizing: border-box;
    }

    /* ---------- Brand bar ---------- */

    .automassms-brand {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding: 14px 18px;
        background: var(--am-brand);
        border-radius: var(--am-radius) var(--am-radius) 0 0;
        color: #fff;
    }

    .automassms-brand-name {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 17px;
        font-weight: 600;
        letter-spacing: .2px;
    }

    .automassms-brand-name .automassms-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .18);
        font-size: 14px;
    }

    .automassms-brand-meta {
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
        font-size: 12px;
    }

    .automassms-brand-meta .automassms-stat {
        line-height: 1.3;
    }

    .automassms-brand-meta .automassms-stat span {
        display: block;
        opacity: .75;
        text-transform: uppercase;
        letter-spacing: .6px;
        font-size: 10px;
    }

    .automassms-brand-meta .automassms-stat strong {
        font-size: 15px;
        font-weight: 600;
    }

    .automassms-brand-meta a {
        color: #fff;
        text-decoration: underline;
    }

    /* ---------- Tab nav ---------- */

    .automassms-nav {
        display: flex;
        flex-wrap: wrap;
        list-style: none;
        margin: 0;
        padding: 0;
        background: var(--am-ink);
    }

    .automassms-nav li {
        flex: 1 1 auto;
    }

    .automassms-nav a {
        display: block;
        padding: 11px 14px;
        text-align: center;
        color: rgba(255, 255, 255, .78);
        text-decoration: none;
        font-weight: 500;
        white-space: nowrap;
        border-bottom: 3px solid transparent;
        transition: background .15s ease, color .15s ease;
    }

    .automassms-nav a:hover,
    .automassms-nav a:focus {
        background: rgba(255, 255, 255, .07);
        color: #fff;
        text-decoration: none;
    }

    .automassms-nav .is-active a {
        background: rgba(255, 255, 255, .12);
        color: #fff;
        border-bottom-color: var(--am-brand);
    }

    .automassms-nav i {
        margin-right: 6px;
    }

    /* ---------- Panels ---------- */

    .automassms-panel {
        background: #fff;
        border: 1px solid var(--am-line);
        border-top: 0;
        border-radius: 0 0 var(--am-radius) var(--am-radius);
        padding: 20px;
    }

    .automassms-panel-narrow {
        max-width: 760px;
    }

    .automassms-section {
        border: 1px solid var(--am-line);
        border-radius: var(--am-radius);
        margin-bottom: 16px;
        overflow: hidden;
    }

    .automassms-section > h4 {
        margin: 0;
        padding: 11px 14px;
        background: var(--am-bg);
        border-bottom: 1px solid var(--am-line);
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--am-muted);
    }

    .automassms-section-body {
        padding: 16px 14px;
    }

    /* ---------- Forms ---------- */

    .automassms-field {
        display: flex;
        flex-wrap: wrap;
        gap: 4px 16px;
        padding: 12px 0;
        border-bottom: 1px dashed var(--am-line);
    }

    .automassms-field:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .automassms-field > label {
        flex: 0 0 190px;
        padding-top: 7px;
        font-weight: 600;
        margin: 0;
    }

    .automassms-field-control {
        flex: 1 1 320px;
        min-width: 0;
    }

    .automassms-wrapper input[type="text"],
    .automassms-wrapper input[type="number"],
    .automassms-wrapper input[type="search"],
    .automassms-wrapper select,
    .automassms-wrapper textarea {
        width: 100%;
        max-width: 460px;
        padding: 7px 10px;
        border: 1px solid var(--am-line);
        border-radius: 4px;
        background: #fff;
        color: var(--am-ink);
        font-size: 13px;
        font-family: inherit;
    }

    .automassms-wrapper textarea {
        max-width: 100%;
        min-height: 74px;
        resize: vertical;
        line-height: 1.5;
    }

    .automassms-wrapper input:focus,
    .automassms-wrapper select:focus,
    .automassms-wrapper textarea:focus {
        outline: 0;
        border-color: var(--am-brand);
        box-shadow: 0 0 0 2px rgba(31, 138, 112, .14);
    }

    .automassms-wrapper input[readonly],
    .automassms-wrapper textarea[readonly] {
        background: var(--am-bg);
        color: var(--am-muted);
    }

    .automassms-hint {
        display: block;
        margin-top: 5px;
        color: var(--am-muted);
        font-size: 12px;
        line-height: 1.5;
    }

    .automassms-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
        padding-top: 14px;
        border-top: 1px solid var(--am-line);
    }

    .automassms-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 16px;
        border: 1px solid transparent;
        border-radius: 4px;
        background: var(--am-brand);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
    }

    .automassms-btn:hover,
    .automassms-btn:focus {
        background: var(--am-brand-dark);
        color: #fff;
        text-decoration: none;
    }

    .automassms-btn-secondary {
        background: #fff;
        border-color: var(--am-line);
        color: var(--am-ink);
    }

    .automassms-btn-secondary:hover,
    .automassms-btn-secondary:focus {
        background: var(--am-bg);
        color: var(--am-ink);
    }

    .automassms-btn-danger {
        background: #c0392b;
    }

    .automassms-btn-danger:hover,
    .automassms-btn-danger:focus {
        background: #a33125;
    }

    .automassms-btn-link {
        background: none;
        border: 0;
        padding: 2px 6px;
        color: var(--am-muted);
        cursor: pointer;
    }

    .automassms-btn-link:hover {
        color: #c0392b;
    }

    /* ---------- Alerts ---------- */

    .automassms-alert {
        padding: 10px 14px;
        border: 1px solid transparent;
        border-radius: 4px;
        margin-bottom: 12px;
        line-height: 1.5;
    }

    .automassms-alert-success {
        background: #eaf6f1;
        border-color: #bfe3d5;
        color: #17604b;
    }

    .automassms-alert-danger {
        background: #fdeded;
        border-color: #f5c6c6;
        color: #8c261c;
    }

    .automassms-alert-warning {
        background: #fdf6e6;
        border-color: #f2dfae;
        color: #7a5b12;
    }

    .automassms-alert-info {
        background: #eef4f9;
        border-color: #cadcea;
        color: #245376;
    }

    /* ---------- Templates ---------- */

    .automassms-template {
        border: 1px solid var(--am-line);
        border-radius: var(--am-radius);
        margin-bottom: 12px;
        overflow: hidden;
    }

    .automassms-template-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 10px 14px;
        background: var(--am-bg);
        border-bottom: 1px solid var(--am-line);
    }

    .automassms-template-head label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        font-weight: 600;
        cursor: pointer;
    }

    .automassms-template-head input[type="checkbox"] {
        margin: 0;
    }

    .automassms-template-desc {
        color: var(--am-muted);
        font-weight: 400;
        font-size: 12px;
    }

    .automassms-template-body {
        padding: 12px 14px;
    }

    .automassms-tokens {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 8px;
    }

    .automassms-token {
        display: inline-block;
        padding: 2px 8px;
        border: 1px solid var(--am-line);
        border-radius: 12px;
        background: var(--am-bg);
        color: var(--am-brand-dark);
        font-family: Menlo, Consolas, monospace;
        font-size: 11px;
        cursor: pointer;
        user-select: none;
    }

    .automassms-token:hover {
        background: var(--am-brand);
        border-color: var(--am-brand);
        color: #fff;
    }

    .automassms-counter {
        margin-top: 6px;
        color: var(--am-muted);
        font-size: 12px;
        text-align: right;
    }

    .automassms-counter.is-over {
        color: #b9541c;
    }

    .automassms-inline-field {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    .automassms-inline-field label {
        margin: 0;
        font-weight: 600;
        white-space: nowrap;
    }

    .automassms-inline-field input {
        max-width: 320px;
    }

    .automassms-inline-field input.automassms-days {
        max-width: 90px;
    }

    /* ---------- Tables ---------- */

    .automassms-table-wrap {
        overflow-x: auto;
        border: 1px solid var(--am-line);
        border-radius: var(--am-radius);
    }

    .automassms-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }

    .automassms-table th,
    .automassms-table td {
        padding: 9px 12px;
        border-bottom: 1px solid var(--am-line);
        text-align: left;
        vertical-align: top;
    }

    .automassms-table th {
        background: var(--am-bg);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-size: 11px;
        color: var(--am-muted);
        white-space: nowrap;
    }

    .automassms-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .automassms-table tbody tr:hover {
        background: #fbfcfd;
    }

    .automassms-message-cell {
        max-width: 380px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .automassms-nowrap {
        white-space: nowrap;
    }

    .automassms-mono {
        font-family: Menlo, Consolas, monospace;
        font-size: 11.5px;
    }

    .automassms-badge {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 11px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .automassms-badge-sent {
        background: #e6f4ee;
        color: #17604b;
    }

    .automassms-badge-failed {
        background: #fdecea;
        color: #8c261c;
    }

    .automassms-badge-neutral {
        background: var(--am-bg);
        color: var(--am-muted);
    }

    /* ---------- Filters / pagination ---------- */

    .automassms-toolbar {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .automassms-toolbar .automassms-toolbar-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .automassms-toolbar label {
        margin: 0;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
        color: var(--am-muted);
    }

    .automassms-toolbar input,
    .automassms-toolbar select {
        max-width: 200px;
    }

    .automassms-toolbar-spacer {
        flex: 1 1 auto;
    }

    .automassms-pager {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 14px;
        color: var(--am-muted);
        font-size: 12px;
    }

    .automassms-pager-links {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .automassms-pager-links a,
    .automassms-pager-links span {
        display: inline-block;
        min-width: 30px;
        padding: 4px 8px;
        border: 1px solid var(--am-line);
        border-radius: 4px;
        text-align: center;
        text-decoration: none;
        color: var(--am-ink);
    }

    .automassms-pager-links .is-current {
        background: var(--am-brand);
        border-color: var(--am-brand);
        color: #fff;
    }

    /* ---------- Stats ---------- */

    .automassms-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
        margin-bottom: 16px;
    }

    .automassms-stat-card {
        padding: 12px 14px;
        border: 1px solid var(--am-line);
        border-radius: var(--am-radius);
        background: var(--am-bg);
    }

    .automassms-stat-card span {
        display: block;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: var(--am-muted);
    }

    .automassms-stat-card strong {
        font-size: 20px;
        font-weight: 600;
    }

    /* ---------- Credit ---------- */

    .automassms-credit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        flex-wrap: wrap;
        padding: 12px 16px 2px;
        color: var(--am-muted);
        font-size: 11.5px;
    }

    .automassms-credit a {
        color: var(--am-brand);
        font-weight: 600;
        text-decoration: none;
    }

    .automassms-credit a:hover,
    .automassms-credit a:focus {
        color: var(--am-brand-dark);
        text-decoration: underline;
    }

    .automassms-credit-sep {
        opacity: .5;
    }

    /* ---------- Empty / error ---------- */

    .automassms-empty {
        padding: 44px 20px;
        text-align: center;
        color: var(--am-muted);
    }

    .automassms-empty h1 {
        font-size: 46px;
        margin: 0 0 6px;
        color: var(--am-line);
    }

    .automassms-empty h3 {
        margin: 0 0 8px;
        color: var(--am-ink);
    }

    @media (max-width: 860px) {
        .automassms-nav a span {
            display: none;
        }

        .automassms-nav i {
            margin-right: 0;
        }

        .automassms-field > label {
            flex: 1 1 100%;
            padding-top: 0;
        }
    }
</style>
