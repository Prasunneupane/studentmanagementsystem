import axios, { AxiosRequestConfig, Method } from 'axios';

// Interface for API response
interface ApiResponse<T> {
  data: T;
  status: number;
}

// ApiService class for handling HTTP requests
class ApiService {
  private baseUrl: string;

  constructor() {
    this.baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
  }

  async request<T>(
    method: Method,
    endpoint: string,
    data: any = null,
    params: any = null,
    withCredentials: true,
  ): Promise<ApiResponse<T>> {
    try {
      const config: AxiosRequestConfig = {
        method,
        url: `${this.baseUrl}${endpoint}`,
        data,
        params,
        headers: {
          'Content-Type': data instanceof FormData ? 'multipart/form-data' : 'application/json',
          'Accept': 'application/json'
        }
      };
      // console.log(config,"config");
      
      const response = await axios(config);
      return {
        data: response.data,
        status: response.status
      };
    } catch (error: any) {
      throw new Error(error.response?.data?.error || 'API request failed');
    }
  }
}

export default new ApiService();