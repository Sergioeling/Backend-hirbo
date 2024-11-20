function injectHTML() {
  const htmlContent = `
    <div class="Hirbo-inicial" id="Hirboinicial">

    <div id="loader" class="loader"></div>
      <div id="widget" class="hidden">
      </div>

      <!---WIDGET CERRADO--> 
      <div class="Hirbo-componente-close"
        style="border: border; border-radius: radio;"
        onclick="openLead()">
        <!--IMAGE-->
        <img class="img-leader" id="IconoPrincipal">
        
        <!--MESSAGE-->
        <div id="Texto-inicial">
        </div>
      </div>
    </div>
    
    <!---WIDGET OPEN-->
    <div class="overContainer" id="overContainer" style="display: none;">
        <img src="https://hirbo.arvispace.com/HirboChatBot/assets/img/agarrado.png" alt="" class="Hirbo-esquina">
        <a href="https://hirbo.arvispace.com/HirboChatBot/#/web" >
          <div id="Hirbo-link">¡Hola, soy HIRBO!</div>
        </a>
    </div>

    <div class="Hirbo-componente-open" id="leaderOpen" style="display: none;">
        <!---COLOR-->
        <div class="Cabecera-hirbo" style="background-color: color; text-align: center; display: flex;">
            
            <!---TITLE-->
            <h2 id="Titulo-cabecera"> Título aquí </h2>

            <button class="Boton-cerrar" onclick="closeFunction()"> X </button>
        </div>

        <!---MESSAGE BOX-->
        <div id="scrollContainer" class="message-container">
          <div class="message"></div>
          <!-- Más mensajes -->
        </div>

        <!---SEND--> 
        <div class="sent" id="send">
          <div style="display: flex">
            <input type="text" id="messageInput" placeholder="Escribe un mensaje..."
            key>
            
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            
            <button class="Boton-enviar-message" id="sendButton" onclick="sendMessage()">
              <img src="https://img.icons8.com/color/48/000000/paper-plane.png"> 
            </button>

          </div>
        </div>
      </div>
    </div>
  `;
  document.getElementById("container").innerHTML = htmlContent;
}

class nodo {
  constructor(item, referencia, subniveles = []) {
    this.item = item;
    this.referencia = referencia;
    this.subniveles = subniveles;
  }
}

let showLeader = false;

let conversation = [];
let stack = [];
let initialTxt = "";
let currentNode;

const scriptElement = document.currentScript;
const scriptSrc = scriptElement.src;
const fileName = scriptSrc.split("/").pop();

const fileNumber = fileName.replace(/\D/g, "");

let hasFetchedData = false;
async function enviarId(id) {
  const url =
    "https://hirbo.arvispace.com/Back/services/Rutas.php?ConfigWidjet";

  document.getElementById("loader").classList.remove("hidden");
  document.getElementById("widget").classList.add("hidden");

  const datos = { id: id };

  try {
    const response = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(datos),
    });

    if (!response.ok) {
      throw new Error(`Error: ${response.status} - ${response.statusText}`);
    }

    const data = await response.json();

    document.getElementById("Texto-inicial").textContent =
      data.response[0].tooltip;
    document.querySelector(".Hirbo-componente-close").style.border =
      data.response[0].border;
    document.querySelector(".Hirbo-componente-close").style.borderRadius =
      data.response[0].radio;
    document.querySelector(".Cabecera-hirbo").style.backgroundColor =
      data.response[0].color;
    document.querySelector(".Hirbo-componente-close").style.backgroundColor =
      data.response[0].color;
    document.getElementById("IconoPrincipal").src = data.response[0].image;
    document.getElementById("Titulo-cabecera").textContent =
      data.response[0].title;
    document.getElementById("sendButton").style.backgroundColor =
      data.response[0].color;

    initialTxt = data.response[0].message;

    document.getElementById("loader").classList.add("hidden");
    document.getElementById("widget").classList.remove("hidden");
  } catch (error) {
    error;
    document.getElementById("loader").classList.add("hidden");
    document.getElementById("widget").classList.remove("hidden");
  }
}

function getFlowApiData() {
  const apiUrl =
    "https://hirbo.arvispace.com/Back/services/Rutas.php?readFlujoLeads";
  const body = { idOrg: fileNumber };

  return fetch(apiUrl, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(body),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Error en la red: " + response.status);
      }
      return response.json();
    })
    .then((data) => {
      root = new nodo(
        initialTxt,
        "Selecciona una opción",
        data.response.map((item) => {
          return new nodo(item.item, item.referencia, item.subniveles);
        })
      );

      currentNode = root;
      stack.push(currentNode);

      showInitData(root);
    })
    .catch((error) => {
    });
}

function showInitData(root) {
  const initialMsg = {
    msg: root.item,
    type: "received",
  };

  const initialOpc = {
    msg: root.referencia,
    type: "received",
  };

  const menu = `
    <ul class="no-bullets">
        ${currentNode.subniveles
          .map((item, index) => `<li>${index + 1}. ${item.item}</li>`)
          .join("")}
    </ul>
`;
  const initMenu = {
    msg: menu,
    type: "received",
  };

  conversation.push(initialMsg);
  conversation.push(initialOpc);
  conversation.push(initMenu);

  displayMessages();
}

function displayMessages() {
  const messageContainer = document.getElementById("scrollContainer");
  messageContainer.innerHTML = "";
  conversation.forEach((item) => {
    const messageElement = document.createElement("div");
    messageElement.className = `${item.type}`;
    messageElement.innerHTML = item.msg;
    messageContainer.appendChild(messageElement);
  });
}

window.addEventListener("load", () => {
  if (!hasFetchedData) {
    hasFetchedData = true;
    enviarId(fileNumber);
  }
});

function openLead() {
  showLeader = true;
  document.getElementById("overContainer").style.display = "block";
  document.getElementById("leaderOpen").style.display = "block";
  getFlowApiData();
}

function closeFunction() {
  showLeader = false;
  conversation = [];
  stack = [];
  document.getElementById("overContainer").style.display = "none";
  document.getElementById("leaderOpen").style.display = "none";
  messageInput.value = "";
}

function sendMessage() {
  const messageInput = document.getElementById("messageInput");
  const message = messageInput.value;

  if (message) {
    conversation.push({
      msg: message,
      type: "sent",
    });
    if (message === "copiar") {
      clonarArchivo();
    } else {
      navigateTochild(Number(message) - 1);
    }

    messageInput.value = "";
    const messageContainer = document.getElementById("scrollContainer");
    messageContainer.scrollTop = messageContainer.scrollHeight;

    displayMessages();
  }
}
function navigateTochild(index) {
  const node = stack[stack.length - 1];

  if (
    node.subniveles[index] &&
    node.subniveles[index].item === "Regresar al menú anterior"
  ) {

    stack.pop();

    const afternode = stack[stack.length - 1];
    if (afternode.referencia.length > 0) {
      conversation.push({
        msg: afternode.referencia,
        type: "received",
      });
    } else {
      conversation.push({
        msg: afternode.item,
        type: "received",
      });
    }

    if (afternode.subniveles.length > 0) {
      const menu = `
          <ul class="no-bullets">
              ${afternode.subniveles
                .map((item, index) => `<li>${index + 1}. ${item.item}</li>`)
                .join("")}
          </ul>
      `;

      conversation.push({
        msg: menu,
        type: "received",
      });
    }

    displayMessages();
  } else {
    if (node && node.subniveles && node.subniveles.length >= 1) {
      if (node.subniveles[index]) {
        stack.push(node.subniveles[index]);
        const actual = stack[stack.length - 1];

        const exists = actual.subniveles.some(
          (subnivel) => subnivel.item === "Regresar al menú anterior"
        );

        if (!exists) {
          const deletes = new nodo("Regresar al menú anterior", "", []);
          actual.subniveles.push(deletes);
        }


        if (actual.referencia.length > 0) {
          conversation.push({
            msg: actual.referencia,
            type: "received",
          });
        } else {
          conversation.push({
            msg: actual.item,
            type: "received",
          });
        }

        if (actual.subniveles.length > 0) {
          const menu = `
                <ul class="no-bullets">
                    ${actual.subniveles
                      .map(
                        (item, index) => `<li>${index + 1}. ${item.item}</li>`
                      )
                      .join("")}
                </ul>
            `;

          conversation.push({
            msg: menu,
            type: "received",
          });
        }

        displayMessages();
      } else {
      }
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  injectHTML();
});
