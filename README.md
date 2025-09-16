# Vue.js + Symfony API Platform + Docker Starter

This project is a starter template that sets up a **Vue.js frontend** with a **Symfony API Platform backend** using **Docker**.

---

## 🚀 Features
- **Symfony 7 + API Platform** as the backend (PHP 8.2).
- **Vue.js 3 + Vite + Vue Router + Vuex** as the frontend.
- **MySQL 8** as the database.
- **Docker & Docker Compose** for containerization.
- **Makefile** for automation (easy commands).

---

## 📂 Project Structure
```
.
├── backend/        # Symfony API Platform project
├── frontend/       # Vue.js frontend project
├── docker-compose.yml
├── Makefile
└── README.md
```

---

## ⚙️ Prerequisites
Make sure you have installed:
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/)
- [Make](https://www.gnu.org/software/make/)

---

## 🛠️ Installation

Clone the repository:
```bash
git clone https://github.com/your-username/your-repo.git
cd your-repo
```

Install dependencies for **backend** and **frontend**:

```bash
make install
```

---

## ▶️ Running the Project

Start all services (backend, frontend, database):
```bash
make up
```

Build/rebuild containers:
```bash
make build
```

Initialize the database (run migrations):
```bash
make db-init
```

Stop containers:
```bash
make stop
```

View logs:
```bash
make logs
```

Access the Symfony backend:
- API Platform: [http://localhost:8000/api](http://localhost:8000/api)
Access the Symfony profiler:
- Profiler: [http://localhost:8000/_profiler](http://localhost:8000/_profiler)
Access the Symfony debug toolbar:
- Toolbar: [http://localhost:8000/_wdt](http://localhost:8000/_wdt)
Access to Symfony docs:
- Docs: [http://localhost:8000/docs](http://localhost:8000/docs)

Access the Vue.js frontend:
- Frontend: [http://localhost:5173](http://localhost:5173)

Access the MySQL database:
- Host: `localhost:8080`
- User: `root`
- Password: `root`
- Database: `symfony`

---

## 🧹 Useful Commands

Run Symfony console:
```bash
make console
```

Run migrations:
```bash
make migrate
```

Access MySQL DB:
```bash
make db
```

---

## 📝 Notes
- Default MySQL credentials are set in `docker-compose.yml`.
- Update `.env` in backend if you need custom DB settings.
- The project is only a **starter kit**. You should adapt entities, Vue components, and authentication as needed.

---

## 📖 Next Steps
- Add authentication (JWT already integrated in Symfony skeleton).
- Customize Vue components for your app needs.
- Extend API Platform with custom operations.

---
Made with ❤️ using Symfony, Vue, and Docker.