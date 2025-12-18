# Arrissa Data API

cd /Users/davidrichchild/Desktop/arrissa-data-api/public && php -S localhost:8000

A clean dashboard framework for market data API applications.

## Features

- ✅ Modern dark theme dashboard
- ✅ Sidebar navigation
- ✅ Empty markets page template
- ✅ No pre-filled data - ready for your integration

## Structure

```
resources/views/
├── layouts/
│   └── app.blade.php      # Main layout with sidebar
├── dashboard.blade.php     # Dashboard page
├── markets.blade.php       # Markets page (main template)
├── portfolio.blade.php     # Portfolio page
├── transactions.blade.php  # Transactions page
├── news.blade.php         # News page
├── calculator.blade.php   # Calculator page
└── settings.blade.php     # Settings page
```

## Getting Started

Open `/markets` route to see the main interface template.

Tell me what data you want to add and I'll help you integrate it!
