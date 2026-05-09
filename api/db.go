package main

import (
	"database/sql"
	"fmt"
	"log"
	"os"

	_ "github.com/go-sql-driver/mysql"
)

var db *sql.DB

func initDB() {
	host := os.Getenv("DB_HOST")
	if host == "" {
		host = "127.0.0.1"
	}

	dbName := os.Getenv("DB_NAME")
	if dbName == "" {
		dbName = "silverhappy"
	}

	envPort := os.Getenv("DB_PORT")
	portCandidates := []string{}
	if envPort != "" {
		portCandidates = append(portCandidates, envPort)
	}
	portCandidates = append(portCandidates, "3306", "8889")

	user := os.Getenv("DB_USER")
	if user == "" {
		user = "root"
	}

	password, passSet := os.LookupEnv("DB_PASS")
	if !passSet {
		password = ""
	}

	type credential struct {
		user     string
		password string
	}

	credentialCandidates := []credential{{user: user, password: password}}
	if user == "root" && password == "" {
		credentialCandidates = append(credentialCandidates, credential{user: "root", password: "root"})
	}

	var lastErr error

	for _, port := range portCandidates {
		for _, creds := range credentialCandidates {
			dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?charset=utf8mb4&parseTime=true",
				creds.user, creds.password, host, port, dbName)

			candidateDB, err := sql.Open("mysql", dsn)
			if err != nil {
				lastErr = err
				continue
			}

			if err := candidateDB.Ping(); err != nil {
				lastErr = err
				_ = candidateDB.Close()
				continue
			}

			db = candidateDB
			fmt.Printf("Connexion MySQL établie (%s:%s)\n", host, port)
			return
		}
	}

	log.Fatal("Impossible de joindre MySQL :", lastErr)
}
