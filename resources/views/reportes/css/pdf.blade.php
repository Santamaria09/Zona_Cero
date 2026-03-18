body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    color: #2b2b2b;
    margin: 30px;
    background: #fafafa;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 2px solid #1a202c;
    margin-bottom: 18px;
    background: #ffffff;
}

.report-logo img {
    width: 80px;
    height: auto;
}

.report-title {
    text-align: center;
    flex: 1;
}

.report-title h1 {
    margin: 0;
    font-size: 20px;
    letter-spacing: 0.3px;
}

.report-subtitle {
    margin: 6px 0 0;
    font-size: 11px;
    color: #4a5568;
}

.report-meta {
    text-align: right;
    font-size: 11px;
    line-height: 1.4;
}

.report-body {
    margin-top: 10px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
    font-size: 11px;
}

.report-table th,
.report-table td {
    padding: 8px 10px;
    border: 1px solid #d6d6d6;
}

.report-table th {
    background: #1a202c;
    color: #ffffff;
    text-align: left;
    font-size: 11px;
}

.report-table tbody tr:nth-child(odd) {
    background: rgba(26, 32, 44, 0.05);
}

.report-table tbody tr:hover {
    background: rgba(26, 32, 44, 0.1);
}

.report-table .empty {
    text-align: center;
    padding: 24px;
    color: #4a5568;
}

.report-footer {
    margin-top: 22px;
    padding-top: 12px;
    border-top: 1px solid #d6d6d6;
    font-size: 10px;
    color: #4a5568;
    text-align: center;
}

@page {
    margin: 30px;
}

