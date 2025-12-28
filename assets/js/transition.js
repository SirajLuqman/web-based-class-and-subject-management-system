document.addEventListener("DOMContentLoaded", () => {
    document.body.classList.add("loaded");
});

// Optional: Fade out before navigating to another page
document.querySelectorAll("a").forEach(link => {
    link.addEventListener("click", function(e){
        const href = this.getAttribute("href");

        
        if(!href.startsWith("#") && !href.startsWith("javascript")) {
            e.preventDefault();
            document.body.classList.remove("loaded");
            setTimeout(() => {
                window.location = href;
            }, 50); // match transition duration
        }
    });
});
