function PackagesGrid({ packages }) {
    if (!packages || packages.length === 0) {
        return React.createElement('div', { className: 'alert alert-info' }, 'No packages found.');
    }

    const { useState, useMemo } = React;
    const PAGE_SIZE = 9;
    const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);

    // Helper to get image URL with fallback
    const getImageUrl = (pkg) => {
        if (pkg.image_url) return pkg.image_url;
        if (pkg.image) return pkg.image;
        return `https://via.placeholder.com/400x200/0d6efd/ffffff?text=${encodeURIComponent(pkg.title)}`;
    };

    const visiblePackages = useMemo(
        () => packages.slice(0, Math.min(visibleCount, packages.length)),
        [packages, visibleCount]
    );

    const grid = React.createElement(
        'div',
        { className: 'row' },
        visiblePackages.map(pkg =>
            React.createElement(
                'div',
                { className: 'col-md-4 mb-4', key: pkg.id },
                React.createElement(
                    'div',
                    { className: 'card h-100 shadow-sm' },
                    React.createElement(
                        'div',
                        { className: 'thumb-box-200' },
                        React.createElement('img', {
                            src: getImageUrl(pkg),
                            alt: pkg.title,
                            onError: function(e) {
                                e.target.src = `https://via.placeholder.com/400x200/0d6efd/ffffff?text=${encodeURIComponent(pkg.title)}`;
                            }
                        })
                    ),
                    React.createElement(
                        'div',
                        { className: 'card-body' },
                        React.createElement('h5', { className: 'card-title' }, pkg.title),
                        React.createElement('p', { className: 'card-text text-muted' }, pkg.description),
                        React.createElement('p', { className: 'card-text text-muted' }, `${pkg.duration || ''} • ₱${pkg.price.toLocaleString()}`),
                        React.createElement(
                            'a',
                            { href: `package.php?id=${pkg.id}`, className: 'btn btn-outline-primary' },
                            'View & Book'
                        )
                    )
                )
            )
        )
    );

    const showMoreBtn = (visibleCount < packages.length)
        ? React.createElement(
            'div',
            { className: 'text-center mt-2' },
            React.createElement(
                'button',
                {
                    className: 'btn btn-primary',
                    onClick: function() { setVisibleCount(c => Math.min(c + PAGE_SIZE, packages.length)); }
                },
                'See more'
            )
        )
        : null;

    return React.createElement(React.Fragment, null, grid, showMoreBtn);
}

document.addEventListener('DOMContentLoaded', function () {
    const rootEl = document.getElementById('packages-root');
    if (rootEl && window.packagesData) {
        const root = ReactDOM.createRoot(rootEl);
        root.render(React.createElement(PackagesGrid, { packages: window.packagesData }));
    }
});