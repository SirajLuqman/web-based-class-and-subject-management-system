<!-- Global Page Transition Styles -->
<style>
body {
    opacity: 0;
    transition: opacity 0.2s ease-in-out; /* faster */
}

.fade-out {
    opacity: 0;
}
</style>

<script>
// Fade-in on page load
document.addEventListener("DOMContentLoaded", function() {
    // make fade-in start only when DOM is ready
    requestAnimationFrame(() => {
        document.body.style.opacity = "1";
    });
});

// Fade-out before leaving page
document.addEventListener("click", function(e) {
    const link = e.target.closest("a");

    if (link && link.href && !link.target) {
        e.preventDefault();
        document.body.classList.add("fade-out");

        setTimeout(() => {
            window.location = link.href;
        }, 200); // faster
    }
});
</script>
