package main

import (
	"database/sql"
	"fmt"
	"log"

	_ "github.com/go-sql-driver/mysql"
)

var db *sql.DB

func initDB() {
	host := "localhost"
	port := "3306"
	dbName := "silverhappy"
	user := "root"
	password := "root"

	dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?charset=utf8mb4&parseTime=true",
		user, password, host, port, dbName)

	var err error
	db, err = sql.Open("mysql", dsn)
	if err != nil {
		log.Fatal("Impossible d'ouvrir la connexion MySQL :", err)
	}

	if err = db.Ping(); err != nil {
		log.Fatal("Impossible de joindre MySQL :", err)
	}

	fmt.Println("Connexion MySQL établie")
}
