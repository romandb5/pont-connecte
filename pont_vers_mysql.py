import paho.mqtt.client as mqtt
import mysql.connector
import json

# --- CONFIGURATION MYSQL ---
DB_HOST = "127.0.0.1"        
DB_PORT = 3307               
DB_USER = "Etudiant"         
DB_PASS = "P@ssword"         
DB_NAME = "pontconnecte"

# --- CONFIGURATION MQTT ---
MQTT_BROKER = "localhost" 
MQTT_PORT = 1883
MQTT_TOPIC = "application/+/device/+/event/up" 

# --- LE MAPPING MAGIQUE (ID de tes capteurs) ---
MAPPING_CAPTEURS = {
    "vibration_m_s2": 1,      # ID 1 : mma8451
    "tds_ppm": 2,             # ID 2 : TDS Meter
    "temperature_c": 3,       # ID 3 : DS18B20
    "profondeur_eau_mm": 4,   # ID 4 : SN0257
    "obstacle_detecte": 5     # ID 5 : E18-D80NK
}

def connect_db():
    return mysql.connector.connect(
        host=DB_HOST,
        port=DB_PORT,        
        user=DB_USER,
        password=DB_PASS,
        database=DB_NAME
    )

def nettoyer_anciennes_donnees(db):
    try:
        cursor = db.cursor()
        # On supprime tout ce qui est plus vieux que 24 heures
        sql = "DELETE FROM MESURES_CAPTEURS WHERE DATE_MESURE < (NOW() - INTERVAL 24 HOUR)"
        cursor.execute(sql)
        db.commit()
        if cursor.rowcount > 0:
            print(f"🧹 Ménage : {cursor.rowcount} anciennes mesures supprimées.")
        cursor.close()
    except Exception as e:
        print(f"⚠️ Erreur lors du nettoyage : {e}")

def on_message(client, userdata, msg):
    print(f"\n📦 Nouveau message LoRaWAN reçu !")
    
    try:
        payload = json.loads(msg.payload.decode('utf-8'))
        
        # On sécurise la lecture : ChirpStack met souvent les données dans "object" ou "objectJSON"
        data = payload.get("object") or payload.get("objectJSON")
        
        if type(data) is str:
            data = json.loads(data) 
            
        if not data:
            print("Aucune donnée décodée (vérifier le codec ChirpStack).")
            return

        db = connect_db()
        cursor = db.cursor()
        
        sql = """INSERT INTO MESURES_CAPTEURS (CAPTEUR_ID, VALEUR, DATE_MESURE) 
                 VALUES (%s, %s, NOW())"""

        insertions_reussies = 0

        for nom_chirpstack, valeur in data.items():
            if nom_chirpstack in MAPPING_CAPTEURS:
                capteur_id = MAPPING_CAPTEURS[nom_chirpstack]
                
                # Conversion du booléen en entier pour MySQL
                if isinstance(valeur, bool):
                    valeur = 1 if valeur else 0

                cursor.execute(sql, (capteur_id, valeur))
                insertions_reussies += 1

        db.commit()
        
        nettoyer_anciennes_donnees(db) 
        
        cursor.close()
        db.close()
        print(f"✅ Données insérées et base de données nettoyée.")

    except Exception as e:
        print(f"❌ Erreur lors du traitement : {e}")

# --- LANCEMENT DU SCRIPT ---
print("🚀 Démarrage de la passerelle ChirpStack -> MySQL...")
client = mqtt.Client()
client.on_message = on_message

try:
    client.connect(MQTT_BROKER, MQTT_PORT, 60)
    client.subscribe(MQTT_TOPIC)
    print("📡 Écoute du réseau en cours... (Appuie sur Ctrl+C pour arrêter)")
    client.loop_forever()
except ConnectionRefusedError:
    print("❌ Impossible de se connecter au broker MQTT. Est-ce que Mosquitto est bien installé et lancé sur le Raspberry Pi ?")