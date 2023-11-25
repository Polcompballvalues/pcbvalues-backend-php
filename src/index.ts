"use strict";
import type { APIResponse } from "./types";

const checkWbhookButton = <HTMLButtonElement>document.getElementById("check-wbhook")!;
const statusDisplay = <HTMLDivElement>document.getElementById("status-display");

async function checkWebhookResponse(): Promise<APIResponse> {
    const resp = await fetch("/testpoint.php");

    if (resp.status > 299) {
        throw new Error(`Recieved error code: ${resp.status}`);
    }

    const contentType = resp.headers.get("Content-Type");

    if (!contentType?.startsWith("application/json")) {
        throw new Error(`Expected 'application/json' content type, got ${contentType}`);
    }

    return resp.json() as Promise<APIResponse>;
}

checkWbhookButton.addEventListener("click", () => {
    checkWebhookResponse().then(
        v => {
            const status = v.code < 300 ? "Good" : "Error: " + v.text;
            statusDisplay.textContent = `HTTP ${v.code}: ${status}`;
        }
    );
});

for (const elm of document.getElementsByClassName("collapsible")) {
    const parent = elm.parentElement!;
    const collapseButton = parent.querySelector("button.collapse-button")!;

    collapseButton.addEventListener("click", () => {
        if (elm.classList.contains("collapsed")) {
            elm.classList.remove("collapsed");
            collapseButton.textContent = "Collapse scores";

        } else {
            elm.classList.add("collapsed");
            collapseButton.textContent = "Expand scores";

        }
    });
}