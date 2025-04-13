document.addEventListener('DOMContentLoaded', function () {
    // Show alerts from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showToast(urlParams.get('success'), 'success');
    } else if (urlParams.has('error')) {
        showToast(urlParams.get('error'), 'danger');
    }

    // Toast Generator
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed bottom-0 end-0 m-3`;
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('show');
            toast.remove();
        }, 3000);
    }

    // Auto-fill age from DOB
    const dobInput = document.querySelector("#dob");
    const ageInput = document.querySelector("#age");
    if (dobInput && ageInput) {
        dobInput.addEventListener('change', () => {
            const dob = new Date(dobInput.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            ageInput.value = age;
        });
    }
});
