"use strict";
import type { APIResponse } from "./types";

const checkWbhookButton = <HTMLButtonElement>document.getElementById("check-wbhook")!;
const statusDisplay = <HTMLDivElement>document.getElementById("status-display");
const tableHeaders = [...document.querySelectorAll<HTMLTableCellElement>("thead tr th")];

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

function sortTable(index: number, reverse: boolean = false) {
    const rows = [...document.querySelectorAll<HTMLTableRowElement>("table tbody tr")];
    if (index === 0) {
        rows.sort((a, b) => {
            const aElm = a.querySelector<HTMLAnchorElement>("td a")!;
            const bElm = b.querySelector<HTMLAnchorElement>("td a")!;

            const comp = aElm.textContent!.localeCompare(bElm.textContent!);

            return reverse ? - comp : comp;
        });
    } else {
        rows.sort((a, b) => {
            const aElm = a.querySelector<HTMLTableCellElement>(`td:nth-of-type(${index + 1})`)!;
            const bElm = b.querySelector<HTMLTableCellElement>(`td:nth-of-type(${index + 1})`)!;

            const comp = parseFloat(aElm.textContent!) - parseFloat(bElm.textContent!);

            return reverse ? comp : - comp;
        });
    }

    const holder = document.querySelector("table tbody")!;
    for (const [i, elm] of rows.entries()) {
        const oldNode = holder.querySelector(`tr:nth-of-type(${i + 1})`)!;

        if (!oldNode.isEqualNode(elm)) {
            holder.insertBefore(elm, oldNode);
        }
    }
}

function resetOthers(index: number) {
    tableHeaders.forEach((elm, i) => {
        if (i !== index) {
            elm.classList.remove("sorted", "reverse");
        }
    });
}

for (const [i, header] of tableHeaders.entries()) {
    header.addEventListener("click", (ev: MouseEvent) => {
        const { target } = ev;
        if (!target || !(target instanceof HTMLElement)) {
            return;
        }

        //Already sorted
        if (target.classList.contains("sorted")) {
            //Already reversed
            if (target.classList.contains("reverse")) {
                sortTable(0);
                //Is title (title gets sorted by default)
                if (i === 0) {
                    target.classList.remove("reverse")
                } else {
                    //Sorts title
                    tableHeaders[0].classList.add("sorted");
                    target.classList.remove("sorted", "reverse");
                }
            } else {
                //Reverse sorts
                sortTable(i, true);
                target.classList.add("reverse");
            }
        } else {
            //Sorts directly and resets others
            sortTable(i);
            target.classList.add("sorted");
            resetOthers(i)
        }
    });
}
