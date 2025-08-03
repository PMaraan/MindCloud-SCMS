document.addEventListener("DOMContentLoaded", () => {
    const profileInput = document.getElementById("profilePicture");
    const profilePreview = document.getElementById("profilePreview");

    profileInput.addEventListener("change", () => {
        const file = profileInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = () => {
                profilePreview.src = reader.result;
            };
            reader.readAsDataURL(file);
        }
    });
});
