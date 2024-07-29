import { environment } from '../../../environments/environment';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { IMqttServiceOptions, MqttService, IMqttMessage } from 'ngx-mqtt';

@Injectable()
export class WorkStationMqttService {
  private topicName: string;

  constructor(private _mqttService: MqttService) {
    this.topicName =
      'RabbitMQMessageTypes.WorkstationStatus, RabbitMQMessageTypes';
  }


}
