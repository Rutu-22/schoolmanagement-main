import { Subject } from 'rxjs';
import { LoaderService } from '../../services/loader.service';
import { Component } from '@angular/core';

@Component({
  selector: 'app-loader',
  templateUrl: './loader.component.html',
  styleUrls: ['./loader.component.scss'],
})
export class LoaderComponent {
  isLoading: Subject<boolean> = this.loader.isLoading;
  constructor(private loader: LoaderService) {

  }
}
