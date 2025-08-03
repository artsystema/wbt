// JS for single task page
document.addEventListener("DOMContentLoaded", () => {
    let passcode = localStorage.getItem("passcode") || "";
    const authField = document.getElementById("authField");
    const authBtn = document.getElementById("authBtn");
    const authStatus = document.getElementById("authStatus");
    const bankDisplay = document.getElementById("bankDisplay");
    const takeBtn = document.getElementById("takeBtn");
    const taskRow = document.getElementById("taskRow");
    const statusCell = document.getElementById("statusCell");

    function updateAuthDisplay() {
        if (passcode) {
            authField.style.display = "none";
            authBtn.style.display = "none";
            authStatus.innerHTML = `Authorized as [<strong>${passcode}</strong>] <button id="deauthBtn">Exit</button>`;
            document.getElementById("deauthBtn").addEventListener("click", () => {
                localStorage.removeItem("passcode");
                passcode = "";
                authField.value = "";
                updateAuthDisplay();
                refreshStatus();
            });
        } else {
            authField.style.display = "";
            authBtn.style.display = "";
            authStatus.textContent = "";
        }
    }

    function refreshStatus() {
        if (!taskRow || !statusCell) return;
        const status = taskRow.dataset.status;
        const owner = taskRow.dataset.owner;
        const start = taskRow.dataset.start;
        const estimatedMs = parseInt(taskRow.dataset.estimatedMs || "0");
        statusCell.innerHTML = "";
        if (status === "in_progress") {
            if (passcode && passcode === owner && start) {
                const endTime = new Date(start).getTime() + estimatedMs;
                const countdown = document.createElement("span");
                countdown.className = "countdown";
                countdown.dataset.end = new Date(endTime).toISOString();
                countdown.dataset.estimatedMs = estimatedMs;
                statusCell.appendChild(countdown);
                updateCountdowns();
            } else {
                statusCell.textContent = "in progress";
            }
        } else {
            statusCell.textContent = status.replace(/_/g, " ");
        }
    }

    authBtn.addEventListener("click", () => {
        const val = authField.value.trim();
        if (!val) return alert("Enter a passcode first.");
        passcode = val;
        localStorage.setItem("passcode", passcode);
        updateAuthDisplay();
        refreshStatus();
    });

    updateAuthDisplay();
    refreshStatus();

    if (takeBtn) {
        takeBtn.addEventListener("click", () => {
            if (!passcode) return alert("Enter passcode first.");
            fetch("/api/tasks.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ task_id: takeBtn.dataset.id, passcode })
            })
                .then(res => res.json())
                .then(result => {
                    if (result.success) location.reload();
                    else alert("Error: " + (result.error || "Unknown"));
                });
        });
    }

    function updateCountdowns() {
        document.querySelectorAll(".countdown").forEach(el => {
            const end = new Date(el.dataset.end);
            const now = new Date();
            const diff = end - now;
            const estimatedMs = parseFloat(el.dataset.estimatedMs || "0");
            if (isNaN(diff) || diff <= 0) {
                el.textContent = "Expired";
            } else {
                const mins = diff / 60000;
                const total = estimatedMs / 1000;
                const elapsed = total - diff / 1000;
                const progressRatio = Math.max(0, Math.min(1, elapsed / total));
                const barSegments = 20;
                const filledSegments = Math.round(barSegments * progressRatio);
                const stripes = Array.from({ length: filledSegments }, () => '<div></div>').join("");
                el.innerHTML = `
                    <div class="progress-bar">
                        <div class="progress-stripe">${stripes}</div>
                    </div>in progress <br> [${formatTime(mins, true)}]
                `;
            }
        });
    }

    function formatTime(minutes, includeSeconds = false) {
        const totalMs = minutes * 60 * 1000;
        const mins = Math.floor(totalMs / 60000);
        const secs = Math.floor((totalMs % 60000) / 1000);
        const hrs = Math.floor(mins / 60);
        const remMins = mins % 60;

        if (includeSeconds) {
            if (hrs > 0) return `${hrs}h ${remMins}m ${secs}s`;
            if (remMins > 0) return `${remMins}m ${secs}s`;
            return `${secs}s`;
        } else {
            if (hrs > 0 && remMins > 0) return `${hrs}h ${remMins}m`;
            if (hrs > 0) return `${hrs}h`;
            return `${remMins}m`;
        }
    }

    setInterval(updateCountdowns, 1000);
    updateCountdowns();

    fetch("/api/fund.php")
        .then(res => res.json())
        .then(bank => {
            const text = `$${bank.available} available`;
            const reserved = bank.reserved > 0 ? ` ($${bank.reserved} reserved)` : '';
            bankDisplay.textContent = text + reserved;
        });
});
