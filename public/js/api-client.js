window.ApiClient = {

  baseUrl: BASE_URL,

  getToken: function () {
    return localStorage.getItem(STORAGE_KEY);
  },

  getUser: function () {
    var raw = localStorage.getItem(STORAGE_USER);
    return raw ? JSON.parse(raw) : null;
  },

  isLoggedIn: function () {
    return !!this.getToken();
  },

  logout: function () {
    localStorage.removeItem(STORAGE_KEY);
    localStorage.removeItem(STORAGE_USER);
  },

  getTokenFromSession: async function () {

    try {

      var resp = await fetch(
        BASE_URL + 'restaurante/api/auth/token.php',
        {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json'
          },
          credentials: 'include'
        }
      );

      var data = await resp.json();

      if (data.success && data.data && data.data.token) {

        localStorage.setItem(STORAGE_KEY, data.data.token);
        localStorage.setItem(
          STORAGE_USER,
          JSON.stringify(data.data.user)
        );

        return true;
      }

      return false;

    } catch (err) {

      console.error('Error obteniendo token:', err.message);
      return false;
    }
  },

  login: async function (email, password) {

    var resp = await fetch(BASE_URL + 'api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        email: email,
        password: password
      })
    });

    var data = await resp.json();

    if (data.success && data.data && data.data.token) {

      localStorage.setItem(STORAGE_KEY, data.data.token);

      localStorage.setItem(
        STORAGE_USER,
        JSON.stringify(data.data.user)
      );
    }

    return data;
  },

  get: async function (endpoint) {
    return this._request('GET', endpoint);
  },

  post: async function (endpoint, body) {
    return this._request('POST', endpoint, body);
  },

  put: async function (endpoint, body) {
    return this._request('PUT', endpoint, body);
  },

  del: async function (endpoint) {
    return this._request('DELETE', endpoint);
  },

  _request: async function (method, endpoint, body) {

    var token = this.getToken();

    var headers = {};

    // Solo poner Content-Type cuando NO sea FormData
    if (!(body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
    }

    if (token) {
      headers['Authorization'] = 'Bearer ' + token;
    }

    var opts = {
      method: method,
      headers: headers,
      credentials: 'include'
    };

    if (body && (method === 'POST' || method === 'PUT')) {

      if (body instanceof FormData) {
        opts.body = body;
      } else {
        opts.body = JSON.stringify(body);
      }
    }

    var url = endpoint;

    if (
      url.indexOf('api_restaurante/') !== 0 &&
      url.indexOf('/api_restaurante/') !== 0
    ) {

      url =
        BASE_URL +
        'api_restaurante' +
        (url.substr(0, 1) === '/' ? url : '/' + url);

    } else if (url.indexOf('/api_restaurante/') === 0) {

      url = BASE_URL + url.substr(1);

    } else if (url.indexOf('api_restaurante/') === 0) {

      url = BASE_URL + url;
    }

    try {

      var resp = await fetch(url, opts);

      var data;

      try {
        data = await resp.json();
      } catch (e) {

        data = {
          success: false,
          message: 'Respuesta inválida del servidor'
        };
      }

      // Guardar código HTTP para manejarlo en la vista
      data.httpCode = resp.status;

      if (resp.status === 401 && this.isLoggedIn()) {

        this.logout();

        if (
          window.location.pathname.indexOf('/auth/login') === -1
        ) {

          window.location.href =
            BASE_URL + 'restaurante/auth/login';
        }
      }

      return data;

    } catch (err) {

      return {
        success: false,
        message: 'Error de conexión: ' + err.message
      };
    }
  },

  showErrors: function (errorData, containerSelector) {

    var container = document.querySelector(containerSelector);

    if (!container) return;

    if (!errorData || !errorData.errors) {

      container.innerHTML =
        '<div class="api-error">' +
        (errorData
          ? this._esc(errorData.message)
          : 'Error desconocido') +
        '</div>';

      container.style.display = 'block';

      return;
    }

    var html = '';

    for (var field in errorData.errors) {

      if (!errorData.errors.hasOwnProperty(field)) {
        continue;
      }

      var msgs = errorData.errors[field];

      if (Array.isArray(msgs)) {

        for (var i = 0; i < msgs.length; i++) {

          html +=
            '<div class="api-error">' +
            this._esc(msgs[i]) +
            '</div>';
        }

      } else {

        html +=
          '<div class="api-error">' +
          this._esc(msgs) +
          '</div>';
      }
    }

    container.innerHTML = html;
    container.style.display = 'block';
  },

  flash: function (type, message) {

    var flashDiv = document.createElement('div');

    flashDiv.className =
      'flash flash-' +
      (type === 'success' ? 'success' : 'error');

    flashDiv.textContent = message;

    flashDiv.onclick = function () {
      flashDiv.remove();
    };

    var container =
      document.querySelector('.rst-page') ||
      document.querySelector('.page-content') ||
      document.body;

    if (container.firstChild) {
      container.insertBefore(
        flashDiv,
        container.firstChild
      );
    } else {
      container.appendChild(flashDiv);
    }

    setTimeout(function () {
      flashDiv.remove();
    }, 5000);
  },

  _esc: function (str) {

    var div = document.createElement('div');

    div.appendChild(
      document.createTextNode(str)
    );

    return div.innerHTML;
  }
};