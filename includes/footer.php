        </main>
    </div>
</div>

<script src="js/signature_pad.js"></script>
<script>
function toggleSidebarToggle() {
    const container = document.getElementById('app-container');
    if (!container) return;
    
    if (window.innerWidth <= 992) {
        container.classList.toggle('mobile-open');
    } else {
        container.classList.toggle('collapsed');
        const isCollapsed = container.classList.contains('collapsed');
        localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
    }
}

// Close mobile drawer when clicking nav item on small screens
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
    navItems.forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                const container = document.getElementById('app-container');
                if (container) container.classList.remove('mobile-open');
            }
        });
    });
});
</script>
</body>
</html>
