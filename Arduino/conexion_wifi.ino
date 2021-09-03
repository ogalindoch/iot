#include <ArduinoWiFiServer.h>
#include <BearSSLHelpers.h>
#include <CertStoreBearSSL.h>
#include <ESP8266WiFi.h>
#include <ESP8266WiFiAP.h>
#include <ESP8266WiFiGeneric.h>
#include <ESP8266WiFiGratuitous.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266WiFiScan.h>
#include <ESP8266WiFiSTA.h>
#include <ESP8266WiFiType.h>
#include <WiFiClient.h>
#include <WiFiClientSecure.h>
#include <WiFiClientSecureBearSSL.h>
#include <WiFiServer.h>
#include <WiFiServerSecure.h>
#include <WiFiServerSecureBearSSL.h>
#include <WiFiUdp.h>

//************************************ INSTRUCCIONES ****************************************************

//--------> necesito 4 valores que voy a manejar: ID: identificador del baño (puede ser la dirección MAC)
//--------> necesito 4 valores que voy a manejar: nivel de batería
//--------> necesito 4 valores que voy a manejar: status sensor de luz
//--------> necesito 4 valores que voy a manejar: status sensor magnético
//--------------------------------------------->: cómo mandar un mensaje post ()

int LEDPin = D2; //pines para el sensor de luz
int LDRPin = D3;//pines para el sensor de luz

String ssdi = "criajsa-wireless"; //para poner el SSDI de mi Internet
String contrasena = "duro-seguro2005"; //la contraseña del Internet

byte cont = 0; //contador inicializador para conexión a Internet (puede ser Int, pero como la tarjeta tiene poca memoria, mejor poner un byte)
byte max_intentos = 50; //numero de intenetos para poder conectarme a la red

void setup ()
{
  pinMode(LEDPin, OUTPUT); //pines para el sensor de luz --> INDICADOR
  pinMode(LDRPin, INPUT); //pines para el sensor de luz ---> SENSOR FOTORESISTENCIA
  //inicial serial
  Serial.begin(115200); //confgutación del setup con una red wifi
  Serial.println("\n"); //dejamos un enter en el monitor serial
  
  //conexión WIFI
  WiFi.begin(ssdi, contrasena);
  while (WiFi.status() !=  WL_CONNECTED and cont < max_intentos) //cuenta hasta 50
  {
    cont ++;
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  //si cont no sobrepasó max_intentos, si seconectó a la red y nos imprimirá la nformación dentro del IF
  if (cont < max_intentos)
  {
    Serial.println ("******* CONECTADO A LA RED WIFI**************");
    Serial.println (WiFi.SSID()); //imprime el nombre de la red wifi a la que se conecto
    Serial.print ("IP: ");
    Serial.println (WiFi.localIP()); //imprime la dirección IP
    Serial.print ("macAddress: ");
    Serial.println (WiFi.macAddress());
    Serial.println ("*********************************************");
  }
  else //si no, no se conectó
  {
    Serial.println ("***************ERROR, NO SE CONECTÓ**************");
    Serial.print ("QUE TRISTE");
    Serial.println("**************************************************"); 
  }
}

void loop()
{
   int value = digitalRead(LDRPin); // LEEMOS LOS VALORES DE LA FOTORESISTENCIA 
   if (value == HIGH) // SI LOS VALORES SON MUY PEQUEÑOS, PREDEMOS EL LED (modificar eniendo ya el módulo)
   {
      //hacemos parpadear el LED indicando que está sin luz o con luz (modificar en la página web)
      digitalWrite(LEDPin, HIGH); 
      Serial.println ("holis");
      delay(50);
      digitalWrite(LEDPin, LOW);
      delay(50);
   }
}
